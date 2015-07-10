#!/bin/sh

alias fnh='find . -not -path "*/\.*" -exec echo " - {}" \;'

echo "Cleaning paths"
fnh -type f -iname "*.svg" -exec sed -i -e "s/\"\(file:\/\/\)\?[A-Z]:\\\\.*\\\/\"/g" {} \;

echo "Fixing permissions"
fnh -type f -not -name "*.sh" -exec chmod 664 {} \;
fnh -type d -exec chmod 775 {} \;
