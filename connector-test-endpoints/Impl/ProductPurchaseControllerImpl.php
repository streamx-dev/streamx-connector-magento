<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment\ItemFactory as ShipmentItemFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use StreamX\ConnectorTestEndpoints\Api\ProductPurchaseControllerInterface;

class ProductPurchaseControllerImpl implements ProductPurchaseControllerInterface
{
    private OrderFactory $orderFactory;
    private InvoiceService $invoiceService;
    private ShipmentFactory $shipmentFactory;
    private ShipmentItemFactory $shipmentItemFactory;
    private CartManagementInterface $cartManagement;
    private CartRepositoryInterface $cartRepository;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        ShipmentFactory $shipmentFactory,
        ShipmentItemFactory $shipmentItemFactory,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->invoiceService = $invoiceService;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentItemFactory = $shipmentItemFactory;
        $this->orderFactory = $orderFactory;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    public function purchaseProduct(int $productId, int $quantity): void {
        $cart = $this->createCartWithProduct($productId, $quantity);
        $this->configureCart($cart);

        $order = $this->placeOrder($cart);
        $this->createInvoice($order);
        $this->createShipment($order);

        $order->setState(Order::STATE_CLOSED)
            ->setStatus(Order::STATE_CLOSED)
            ->save();
    }

    private function createCartWithProduct(int $productId, int $quantity): CartInterface {
        $cartId = $this->cartManagement->createEmptyCart();
        $cart = $this->cartRepository->get($cartId);
        $cart->setStoreId(1);

        $cart->addProduct(
            $this->productRepository->getById($productId),
            $quantity
        );
        return $cart;
    }

    private function configureCart(CartInterface $cart): void {
        $cart->setCustomerEmail('roni_cost@example.com');
        $this->fillAddress($cart->getBillingAddress());
        $this->fillAddress($cart->getShippingAddress());
        $cart->getPayment()->setMethod('checkmo');
    }

    private function fillAddress(Address $address): void {
        $address->setEmail('roni_cost@example.com')
            ->setFirstname('Veronica')
            ->setLastname('Costello')
            ->setStreet('6146 Honey Bluff Parkway')
            ->setCountryId('US')
            ->setRegionId('31')
            ->setCity('Calder')
            ->setPostcode('49628-7978')
            ->setTelephone('(555) 229-3326')
            ->setShippingMethod('flatrate_flatrate');
    }

    private function placeOrder(CartInterface $cart): Order {
        $orderId = $this->cartManagement->placeOrder($cart->getId());
        return $this->orderFactory->create()->load($orderId);
    }

    private function createInvoice(Order $order): void {
        $this->invoiceService
            ->prepareInvoice($order)
            ->register()
            ->save();
    }

    private function createShipment(Order $order): void {
        $shipmentItems = array_map(
            function (OrderItem $orderItem) {
                return $this->shipmentItemFactory->create()
                    ->setOrderItem($orderItem)
                    ->setQty($orderItem->getQtyOrdered());
            },
            $order->getItems()
        );

        $this->shipmentFactory->create($order)
            ->setItems($shipmentItems)
            ->register()
            ->save();
    }
}