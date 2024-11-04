#!/bin/bash

# Set the script to stop on errors
set -e

# Determine the directory of the script
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# Define the path to the docker-compose file (relative to the script directory)
DOCKER_COMPOSE_FILE="$SCRIPT_DIR/../compose.yml"

# Check if the docker-compose.yml file exists
if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
    echo "File $DOCKER_COMPOSE_FILE does not exist."
    exit 1
fi

# Variable to keep track whether we are in the secrets section
in_secrets_section=false

# Counters for created and skipped files
files_created=0
files_skipped=0

# Read the docker-compose.yml and create empty files for each secret
while IFS= read -r line; do
    # Trim leading and trailing whitespace
    trimmed_line=$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')

    # Check if the line indicates the start of the secrets section when not already in it
    if ! $in_secrets_section && [[ "$line" == "secrets:" ]]; then
        in_secrets_section=true
        continue
    fi

    # If we're in the secrets section, process the lines
    if $in_secrets_section; then
        # If we encounter a new top-level section again, stop processing
        if [[ "$line" =~ ^[[:alpha:]]+: ]]; then
            break
        fi

        # Check if the line contains a file attribute
        if [[ "$trimmed_line" =~ ^file:\ +(.+)$ ]]; then
            file_path=${BASH_REMATCH[1]}

            # Construct the file path correctly, removing extra 'secrets/' if exists
            if [[ "$file_path" == /* ]]; then
                # If the file path is absolute, use it directly
                absolute_file_path="$file_path"
            else
                # If the file path is relative, make sure it relates correctly to SCRIPT_DIR
                absolute_file_path="$SCRIPT_DIR/$(basename $file_path)"
            fi

            # Ensure the file path is within the script's directory (SCRIPT_DIR)
            if [[ "$absolute_file_path" == "$SCRIPT_DIR/"* ]]; then
                directory_path=$(dirname "$absolute_file_path")
                # Create the necessary directories if they do not exist
                if [ ! -d "$directory_path" ]; then
                    mkdir -p "$directory_path"
                fi

                # Create the file if it does not exist
                if [ ! -f "$absolute_file_path" ]; then
                    echo "Creating empty file: $absolute_file_path"
                    touch "$absolute_file_path"
                    files_created=$((files_created + 1))
                else
                    echo "File already exists, skipping: $absolute_file_path"
                    files_skipped=$((files_skipped + 1))
                fi
            else
                echo "Error: Secret file $absolute_file_path is not in the intended directory. Skipping."
                files_skipped=$((files_skipped + 1))
            fi
            continue
        fi
    fi
done < "$DOCKER_COMPOSE_FILE"

echo "Summary: Files created: $files_created, Files skipped: $files_skipped"
