### How to add RabbitMQ service to your Adobe Magento Commerce Cloud

1. Edit `.magento/services.yaml` file by adding the below section:
    ```yaml
    rabbitmq:
      type: rabbitmq:4.0
      disk: 1024
    ```

2. Edit `.magento.app.yaml` by adding the following line in the `relationships:` section:
    ```yaml
        rabbitmq: "rabbitmq:rabbitmq"
    ```

3. Push and deploy the changes

4. Successful deployment should result in `app/etc/env.php` being updated to specify RabbitMQ as `amqp queue` provider.

5. Alternatively, you can configure Magento to use your external RabbitMQ server by using the `bin/magento setup:config:set` command:
   ```bash
   bin/magento setup:config:set --amqp-host="rabbitmq.example.com" --amqp-port="11213" --amqp-user="magento" --amqp-password="magento" --amqp-virtualhost="/"
   ```