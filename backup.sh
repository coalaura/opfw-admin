#!/bin/bash

echo "Creating archive of files..."
tar -cf files.tar public/_discord_attachments public/_transcripts public/_uploads

echo "Creating compressed backup..."
filename=$(date +%d%m%Y_%H%M%S)

# Deprioritizes the processes for both CPU time and io usage
# It was eating up resources every time making the panel slow af
nice -n 19 ionice -c3 tar -cjf panel_$filename.tar.bz2 files.tar

rm files.tar

echo "Backup complete!"