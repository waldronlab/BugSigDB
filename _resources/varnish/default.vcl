vcl 4.0;
# set default backend if no server cluster specified
backend default {
    .host = "web";
    .port = "80";
    .first_byte_timeout = 600s; # How long to wait before we receive a first byte from our backend?
    .connect_timeout = 600s; # How long to wait for a backend connection?
    .between_bytes_timeout = 120s; # How long to wait between bytes received from our backend?
}

# access control list for "purge": open to only localhost and other local nodes
acl purge {
    "10.0.0.0"/8; # RFC1918 possible internal network
    "172.16.0.0"/12; # RFC1918 possible internal network
    "192.168.0.0"/16; # RFC1918 possible internal network
#    "fc00::"/7; # RFC 4193 local private network range
#    "fe80::"/10; # RFC 4291 link-local (directly plugged) machines
}

# vcl_recv is called whenever a request is received
sub vcl_recv {
        # Serve objects up to 2 minutes past their expiry if the backend
        # is slow to respond.
        set req.grace = 120s;
#        set req.http.X-Forwarded-For = client.ip;
        set req.backend_hint = default;

        # This uses the ACL action called "purge". Basically if a request to
        # PURGE the cache comes from anywhere other than localhost, ignore it.
        if (req.method == "PURGE") {
            if (!client.ip ~ purge || ("" + client.ip) ~ "\.1$") { // don't allow to purge from docker gateways
                return (synth(405, "Not allowed."));
            } else {
                return (purge);
            }
        }

        # Pass any requests that Varnish does not understand straight to the backend.
        if (req.method != "GET" && req.method != "HEAD" &&
            req.method != "PUT" && req.method != "POST" &&
            req.method != "TRACE" && req.method != "OPTIONS" &&
            req.method != "DELETE") {
                return (pipe);
        } /* Non-RFC2616 or CONNECT which is weird. */

        # Pass anything other than GET and HEAD directly.
        if (req.method != "GET" && req.method != "HEAD") {
            return (pass);
        }      /* We only deal with GET and HEAD by default */

        # Pass images
        if (req.url ~ "^/w/images/") {
            return(pass);
        }
        # Pass parsoid
        if (req.url ~ "^/w/rest.php/") {
            return(pass);
        }
        # Pass API
        if (req.url ~ "^/w/api.php") {
            return(pass);
        }
        # Pass sitemap
        if (req.url ~ "^/w/sitemap/") {
            return(pass);
        }

        # Pass requests from logged-in users directly.
        # Only detect cookies with "session" and "Token" in file name, otherwise nothing get cached.
        if (req.http.Authorization || req.http.Cookie ~ "_session" || req.http.Cookie ~ "Token") {
            return (pass);
        } /* Not cacheable by default */

        # Pass any requests with the "If-None-Match" header directly.
        if (req.http.If-None-Match) {
            return (pass);
        }

        # normalize Accept-Encoding to reduce vary
        if (req.http.Accept-Encoding) {
            unset req.http.Accept-Encoding;
        }

        # latency is paramount, the backend always provides an accurate Cache-Control I hope
        set req.hash_ignore_vary = true;
        return (hash);
}

# Process any "PURGE" requests converting
# them to GET and restarting for the articles
sub vcl_purge {
    if (!req.url ~ "^/w/") {
        set req.method = "GET";
        if (req.http.X-Host) {
            set req.http.host = req.http.X-Host;
        }
        return (restart);
    }
}

sub vcl_pipe {
    # Note that only the first request to the backend will have
    # X-Forwarded-For set.  If you use X-Forwarded-For and want to
    # have it set for all requests, make sure to have:
    # set req.http.connection = "close";
    # This is otherwise not necessary if you do not do any request rewriting.
    set req.http.connection = "close";
}

# Called after a document has been successfully retrieved from the backend.
sub vcl_backend_response {
    # Don't cache 50x responses
    if (beresp.status >= 500) {
        call beresp_hitmiss;
    }
    if (!beresp.ttl > 0s) {
        call beresp_hitmiss;
    }
    if (beresp.http.Set-Cookie) {
        call beresp_hitmiss;
    }
    if (beresp.http.Surrogate-control ~ "(?i)no-store" ||
      (!beresp.http.Surrogate-Control &&
      beresp.http.Cache-Control ~ "(?i:no-cache|no-store|private)")) {
        call beresp_hitmiss;
    }
    if (beresp.http.Authorization) {
        call beresp_hitmiss;
    }

    # set minimum timeouts to auto-discard stored objects
    set beresp.grace = 4w;
    // no keep - the grace should be enough for 304 candidates
    return (deliver);
}

sub beresp_hitmiss {
    set beresp.ttl = 120s;
    set beresp.uncacheable = true;
    return (deliver);
}

# See https://varnish-cache.org/security/VSV00008.html#vsv00008
sub vsv8 {
    if ((req.http.Content-Length || req.http.Transfer-Encoding) &&
      req.proto != "HTTP/2.0") {
        set resp.http.Connection = "close";
    }
}

sub vcl_synth { call vsv8; }
sub vcl_deliver { call vsv8; }

