#!/bin/bash
# start-container.sh - Railway startup script for myITS Merchandise

echo "🚀 Starting myITS Merchandise..."

# Copy sample configs to actual configs if they don't exist
# On Railway, these will use environment variables

cd /app/src/api

# Database config
if [ ! -f db.php ]; then
    echo "📄 Creating db.php from sample..."
    cp db.sample.php db.php
fi

# Midtrans config
if [ ! -f midtrans_config.php ]; then
    echo "📄 Creating midtrans_config.php from sample..."
    cp midtrans_config.sample.php midtrans_config.php
fi

# RajaOngkir config
if [ ! -f rajaongkir_config.php ]; then
    echo "📄 Creating rajaongkir_config.php from sample..."
    cp rajaongkir_config.sample.php rajaongkir_config.php
fi

cd /app

echo "✅ Config files ready!"
echo "🌐 Starting FrankenPHP server..."

# Start FrankenPHP with Caddyfile
exec frankenphp run --config /app/Caddyfile
