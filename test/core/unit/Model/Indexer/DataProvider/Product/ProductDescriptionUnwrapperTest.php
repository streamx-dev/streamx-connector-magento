<?php

namespace StreamX\ConnectorCore\test\unit\Model\Indexer\DataProvider\Product;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductDescriptionUnwrapper;

class ProductDescriptionUnwrapperTest extends TestCase {

    /** @test */
    public function shouldUnwrapDescription() {
        // given
        $wrappedDescription =
'<div data-content-type="html" data-appearance="default" data-element="main">&lt;b&gt;Hello World!&lt;/b&gt;
&lt;p&gt;Good for beach trips, track meets, yoga retreats and more, the Impulse Duffle is the companion you\'ll want at your side. A large U-shaped opening makes packing a hassle-free affair, while a zippered interior pocket keeps jewelry and other small valuables safely tucked out of sight.&lt;/p&gt;
&lt;ul&gt;
&lt;li&gt;Wheeled.&lt;/li&gt;
&lt;li&gt;Dual carry handles.&lt;/li&gt;
&lt;li&gt;Retractable top handle.&lt;/li&gt;
&lt;li&gt;W 14" x H 26" x D 11".&lt;/li&gt;
&lt;/ul&gt;
</div>';

        // when
        $unwrappedDescription = ProductDescriptionUnwrapper::unwrapIfWrapped($wrappedDescription);

        // then
        $this->assertEquals(
'<b>Hello World!</b>
<p>Good for beach trips, track meets, yoga retreats and more, the Impulse Duffle is the companion you\'ll want at your side. A large U-shaped opening makes packing a hassle-free affair, while a zippered interior pocket keeps jewelry and other small valuables safely tucked out of sight.</p>
<ul>
<li>Wheeled.</li>
<li>Dual carry handles.</li>
<li>Retractable top handle.</li>
<li>W 14" x H 26" x D 11".</li>
</ul>
',
            $unwrappedDescription
        );
    }

    /** @test */
    public function shouldNotUnwrapDescriptionNotWrappedDescription() {
        // given
        $notWrappedDescription = 'The best product';

        // when
        $unwrappedDescription = ProductDescriptionUnwrapper::unwrapIfWrapped($notWrappedDescription);

        // then
        $this->assertEquals($notWrappedDescription, $unwrappedDescription);
    }

}
