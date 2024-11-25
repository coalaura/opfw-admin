#!/bin/bash

echo "Creating archive of files..."
tar -cf files.tar public/_discord_attachments public/_transcripts public/_uploads

echo "Creating compressed backup..."
filename=$(date +%d%m%Y_%H%M%S)

zstd -r -T0 files.tar -o panel_$filename.zst

rm files.tar

echo "Backup complete!"