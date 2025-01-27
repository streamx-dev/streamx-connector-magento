StreamX Magento Connector internally uses [Magento Indexing](https://developer.adobe.com/commerce/php/development/components/indexing/)

You can list all StreamX Indexers using:
```bash
bin/magento indexer:status | grep streamx
```

You can also view them using Magento Admin page.<br />
Go to: SYSTEM -> Tools -> Index Management

All Magento Indexers (including StreamX Indexers) operate in one of two modes:
 - Update On Save
 - Update By Schedule

You can change current mode of an index by selecting its row in the Index Management table and then in the Actions list above - switch between modes.

Description of how StreamX Indexers work when they are configured to operate in the two modes:
 - Update On Save: changes to products / categories / attributes detected while normal work of Magento application are intercepted
   and the entities data is immediately published (or unpublished, depending on the operation) to StreamX.
   Example: when an Admin edits a product via Admin Page
 - Update By Schedule: all changes to products / categories / attributes detected while normal work of Magento application - but also resulting from direct SQL DB changes -
   are collected in changelog tables for each indexer (example table: `streamx_product_indexer_cl`). Internally, the MView mechanism is used.
   The collected changes can then be published to StreamX by manually updating (materializing) the associated MViews - see `StreamxIndexerMviewProcessor` class.
   This class is also designed to be embedded into the Magento Cron functionality, to be executed periodically if needed.

It is also possible to publish all currently available products or categories by using the following command:
```bash
bin/magento indexer:reindex [indexer-name]

# for example:
bin/magento indexer:reindex streamx_category_indexer
```

StreamX Connector comes with additional commands to publish entities to StreamX.

The following command triggers all StreamX Indexers to publish data coming from Store with ID 1:
```bash
bin/magento streamx:reindex --store=1
```

It is also possible to trigger publishing a single entity, using the following command:
```bash
bin/magento streamx:index [indexer-name] [store-id] [entity-id]

# for example, to publish product with ID 6 from store 1 to StreamX:
bin/magento streamx:index streamx_product_indexer 1 6
```

You can also perform any standard `bin/magento indexer:[operation-name]` operations on the StreamX Indexers (one example is `indexer:reset` command).

The list of available indexer operations can be retrieved by executing:
```bash
bin/magento indexer:
```