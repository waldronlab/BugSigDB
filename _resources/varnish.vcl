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
        # set req.grace = 120s;
#        set req.http.X-Forwarded-For = client.ip;
        set req.backend_hint= default;

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

        call mobile_detect;

        # Pass anything other than GET and HEAD directly.
        if (req.method != "GET" && req.method != "HEAD") {
            return (pass);
        }      /* We only deal with GET and HEAD by default */

		# Pass images
		if (req.url ~ "/w/images/") {
            return(pass);
        }

        # Pass parsoid
        if (req.url ~ "/w/rest.php/") {
            return(pass);
        }

        # Pass API
        if (req.url ~ "/w/api.php") {
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

        # Force lookup if the request is a no-cache request from the client.
        if (req.http.Cache-Control ~ "no-cache") {
            ban(req.url);
        }

        # normalize Accept-Encoding to reduce vary
        if (req.http.Accept-Encoding) {
          if (req.http.User-Agent ~ "MSIE 6") {
            unset req.http.Accept-Encoding;
          } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
          } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
          } else {
            unset req.http.Accept-Encoding;
          }
        }

        return (hash);
}

sub vcl_pipe {
        # Note that only the first request to the backend will have
        # X-Forwarded-For set.  If you use X-Forwarded-For and want to
        # have it set for all requests, make sure to have:
        # set req.http.connection = "close";

        # This is otherwise not necessary if you do not do any request rewriting.

        set req.http.connection = "close";
}

# Called if the cache has a copy of the page.
sub vcl_hit {
        if (req.method == "PURGE") {
            ban(req.url);
            return (synth(200, "Purged"));
        }

        if (!obj.ttl > 0s) {
            return (pass);
        }
}

# Called if the cache does not have a copy of the page.
sub vcl_miss {
        if (req.method == "PURGE")  {
            return (synth(200, "Not in cache"));
        }
}

# Called after a document has been successfully retrieved from the backend.
sub vcl_backend_response {
        # Don't cache 50x responses
        if (beresp.status == 500 || beresp.status == 502 || beresp.status == 503 || beresp.status == 504) {
            set beresp.uncacheable = true;
            return (deliver);
        }

        # set minimum timeouts to auto-discard stored objects
        set beresp.grace = 120s;

        if (beresp.ttl < 48h) { # Modified!: set to 1h instead of 48h
          set beresp.ttl = 48h;
        }

        if (!beresp.ttl > 0s) {
          set beresp.uncacheable = true;
          return (deliver);
        }

        if (beresp.http.Set-Cookie) {
          set beresp.uncacheable = true;
          return (deliver);
        }

#        if (beresp.http.Cache-Control ~ "(private|no-cache|no-store)") {
#          set beresp.uncacheable = true;
#          return (deliver);
#        }

        if (beresp.http.Authorization && !beresp.http.Cache-Control ~ "public") {
          set beresp.uncacheable = true;
          return (deliver);
        }

        return (deliver);
}

sub vcl_hash {
	# Cache the mobile version of pages separately.
	if ( req.http.X-Device ) {
		hash_data(req.http.X-Device);
	}
}

sub mobile_detect {
	# Default to thinking it's a PC
	set req.http.X-Device = "pc";

    if ( (req.http.User-Agent ~ "(?i)(mobi|240x240|240x320|320x320|alcatel|android|audiovox|bada|benq|blackberry|cdm-|compal-|docomo|ericsson|hiptop|htc[-_]|huawei|ipod|kddi-|kindle|meego|midp|mitsu|mmp\/|mot-|motor|ngm_|nintendo|opera.m|palm|panasonic|philips|phone|playstation|portalmmm|sagem-|samsung|sanyo|sec-|semc-browser|sendo|sharp|silk|softbank|symbian|teleca|up.browser|vodafone|webos)"
            || req.http.User-Agent ~ "^(?i)(lge?|sie|nec|sgh|pg)-" || req.http.Accept ~ "vnd.wap.wml")
        && req.http.User-Agent !~ "(SMART-TV.*SamsungBrowser)" )
    {
        set req.http.X-Device = "mobile";
    }
}
