$CREATE_RELEASE()

set -Eeuo pipefail
cd "$FORGE_RELEASE_DIRECTORY"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

shared_storage="$FORGE_SITE_ROOT/storage"
mkdir -p "$shared_storage/app/public" \
  "$shared_storage/framework/cache/data" \
  "$shared_storage/framework/sessions" \
  "$shared_storage/framework/views" \
  "$shared_storage/logs"
if [ -e "$FORGE_RELEASE_DIRECTORY/storage" ] || [ -L "$FORGE_RELEASE_DIRECTORY/storage" ]; then
  rm -rf "$FORGE_RELEASE_DIRECTORY/storage"
fi
ln -s "$shared_storage" "$FORGE_RELEASE_DIRECTORY/storage"

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize

$ACTIVATE_RELEASE()
$FORGE_PHP artisan storage:link
$RESTART_QUEUES()
