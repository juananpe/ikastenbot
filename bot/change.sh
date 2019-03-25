# ngrok http -host-header=rewrite virtualhost:80
URL=`curl -s http://localhost:4040/api/tunnels | jq -r '.tunnels[]| select(.proto|contains("https")) | .public_url'`
gsed -i "s#^\(TELEGRAM_BOT_HOOK_URL\s*=\s*\).*\$#\1'${URL}/'#" .env.local
php bin/console  app:webhook --set
