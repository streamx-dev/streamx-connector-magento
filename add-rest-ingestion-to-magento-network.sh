docker network ls | grep magento_default | cut -d ' ' -f1 | xargs -I magento_network_id docker network connect magento_network_id rest-ingestion
docker network ls | grep magento_default | cut -d ' ' -f1 | xargs -I magento_network_id docker network connect magento_network_id blueprint-web.webserver
docker cp nginx.conf magento-app-1:/etc/nginx/conf.d/default.conf
docker exec magento-app-1 nginx -s reload
