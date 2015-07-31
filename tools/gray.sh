#!/bin/bash -e

if [ $# != 2 ]; then
	echo "Usage: $0 <input.bin> <output.pbm>" 1>&2
	exit 1
fi

INFILE="$1"
OUTFILE="$2"

SIZE=$(stat -c %s "$INFILE")
SIZE=$(($SIZE * 8))

echo "P1" > "$OUTFILE"
echo "7 $SIZE" >> "$OUTFILE"

CNT=0
od -td1 -v -An -w1 "$INFILE" | while read BYTE; do
	for BIT in {0..7}; do
		if [ $(($BYTE & 1 << $BIT)) -ne 0 ]; then
			CNT=$(($CNT + 1 & 3))
		else
			CNT=$(($CNT - 1 & 3))
		fi

		case $CNT in
			0)
				echo "1 0 0 0 0 0 1" >> "$OUTFILE"
				;;
			1)
				echo "1 0 0 1 0 0 1" >> "$OUTFILE"
				;;
			2)
				echo "1 0 1 1 1 0 1" >> "$OUTFILE"
				;;
			3)
				echo "1 0 1 0 1 0 1" >> "$OUTFILE"
				;;
		esac
	done
done
