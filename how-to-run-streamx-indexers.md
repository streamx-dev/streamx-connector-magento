StreamX Magento Connector internally uses [Magento Indexing](https://developer.adobe.com/commerce/php/development/components/indexing/)

## List all StreamX Indexers:

You can list all StreamX Indexers using:
```bash
bin/magento indexer:status | grep streamx
```

You can also view them using Magento Admin page.<br />
Go to: SYSTEM -> Tools -> Index Management

## Indexer modes

All Magento Indexers (including StreamX Indexers) operate in one of two modes:
 - Update On Save
 - Update By Schedule

You can change current mode of an index by selecting its row in the Index Management table and then in the Actions list above - switch between modes.

### Description of Indexer Modes

Each StreamX Indexer can be configured to work in one of the two modes:
 - `Update On Save`: changes to products / categories / attributes detected while normal work of Magento application are intercepted
   and the entities data is immediately published (or unpublished, depending on the operation) to StreamX.
   Example: when an Admin edits a product via Admin Page
<br /><br />
 - `Update By Schedule`: all changes to products / categories / attributes detected while normal work of Magento application - but also resulting from direct SQL DB changes -
   are collected in changelog tables for each indexer (example table: `streamx_product_indexer_cl`). Internally, the MView mechanism is used.
   The collected changes can then be published to StreamX by manually updating (materializing) the associated MViews - see `StreamxIndexerMviewProcessor` class.
   This class is also designed to be embedded into the Magento Cron functionality, to be executed periodically if needed.

### Triggering `Update By Schedule` Indexers using cron jobs:

StreamX Indexers come with their own cron definitions.
They are by default configured to be executed every minute - see `src/catalog/etc/crontab.xml`

To enable running cron jobs on your Magento instance, execute
```bash
bin/cron start
```

All Magento cron jobs (including StreamX Indexer cron jobs) can be executed by using the command:
```bash
bin/magento cron:run
```

To allow Magento cron jobs to be executed automatically, you can add them to the `crontab` of your server:
 - execute from terminal:<br />
     `crontab -e`
 - a text editor should appear. By default, it's `vim`
 - add the following line:<br />
     `*/5 * * * * absolute-path-to-your-magento-instance/bin/magento cron:run >> /absolute-path-to-your-magento-instance/src/var/log/cron.log 2>&1`
 - this entry will cause executing `bin/magento cron:run` every five seconds, to give the magento cron jobs the chance to be executed
 - note: even though we've used 5 seconds interval in the above command, the mechanism will respect magento cron settings - that is, StreamX Indexer cron jobs will actually be executed at most once per minute
 - save changes (typically by using `:wq`, when your editor is `vim`)
 - verify if changes are saved:<br />
     `crontab -l`

## Custom execution of StreamX Indexers
It is possible to publish all currently available products or categories by using the following command:
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