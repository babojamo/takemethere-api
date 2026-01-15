docker exec -it gora_postgres psql -U sppms_user -d sppms_db -c "CREATE EXTENSION IF NOT EXISTS postgis;"
docker exec -it gora_postgres psql -U sppms_user -d sppms_db -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"
