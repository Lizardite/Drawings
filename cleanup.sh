#!/bin/sh

alias fnh='find . -not -path "*/\.*" -exec echo " - {}" \;'

echo "Cleaning paths"
fnh -type f -iname "*.svg" -exec sed -i '

# Removes line feeds between parameters in XML tags
:a /<[^>]*$/N; s/\n\s*/ /g; ta;

# Trims absolute paths, converting them to relative paths
s/"file:\/\/[A-Z]:\\[^"]*\\/"/g;

# Hides layers named "whatever[nsfw]"
s/<g\b[^>]*\binkscape:label=".*\[nsfw\]"/&/g; t nsfw; b; :nsfw s/style="display:none"/&/; t; s/>/ style="display:none">/

' {} \;

echo "Fixing permissions"
fnh -type f -not -name "*.sh" -exec chmod 664 {} \;
fnh -type d -exec chmod 775 {} \;
