<?php

namespace StreamX\ConnectorCore\test\Client\RabbitMQ;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCore\Client\Model\Data;
use StreamX\ConnectorCore\Client\RabbitMQ\IngestionRequest;

class IngestionRequestTest extends TestCase {

    /** @test */
    public function shouldConvertToAndFromJson() {
        // given: prepare publish / unpublish messages; with or without additional fields
        $message1 = Message::newPublishMessage('publish-key-1', new Data("Data to be published 1"))->build();
        $message2 = Message::newPublishMessage('publish-key-2', new Data("Data to be published 2"))
            ->withEventTime(123456)
            ->withProperty('prop 1', 'value 1')
            ->withProperty('prop 2', 'value 2')
            ->withPayload(new Data("Overwritten data to be published 2"))
            ->build();

        $message3 = Message::newUnpublishMessage('unpublish-key-1')->build();
        $message4 = Message::newUnpublishMessage('unpublish-key-2')
            ->withEventTime(654321)
            ->withProperties(['prop 3' => 'value 3', 'prop 4' => 'value 4'])
            ->build();

        $storeId = 5;

        $ingestionRequest = new IngestionRequest([$message1, $message2, $message3, $message4], $storeId);

        // when 1:
        $ingestionRequestAsJson = $ingestionRequest->toJson();

        // then
        $expectedIngestionRequestAsJson =
            '{
                "ingestionMessages": [ {
                    "action": "publish",
                    "eventTime": null,
                    "key": "publish-key-1",
                    "payload": {
                        "content": {
                            "bytes": "Data to be published 1"
                        }
                    },
                    "properties": {}
                }, {
                    "action": "publish",
                    "eventTime": {
                        "long": 123456
                    },
                    "key": "publish-key-2",
                    "payload": {
                        "content": {
                            "bytes": "Overwritten data to be published 2"
                        }
                    },
                    "properties": {
                        "prop 1": "value 1",
                        "prop 2": "value 2"
                    }
                }, {
                    "action": "unpublish",
                    "eventTime": null,
                    "key": "unpublish-key-1",
                    "payload": null,
                    "properties": {}
                }, {
                    "action": "unpublish",
                    "eventTime": {
                        "long": 654321
                    },
                    "key": "unpublish-key-2",
                    "payload": null,
                    "properties": {
                        "prop 3": "value 3",
                        "prop 4": "value 4"
                    }
                } ],
                "storeId": 5
            }';
        $this->assertJsonStringEqualsJsonString($expectedIngestionRequestAsJson, $ingestionRequestAsJson);

        // when 2:
        $recreatedIngestionRequest = IngestionRequest::fromJson($ingestionRequestAsJson);

        // then
        $this->assertEquals($recreatedIngestionRequest, $ingestionRequest);

        // when 3:
        $recreatedIngestionRequestAsJson = $recreatedIngestionRequest->toJson();

        // then
        $this->assertJsonStringEqualsJsonString($recreatedIngestionRequestAsJson, $ingestionRequestAsJson);
    }
}
