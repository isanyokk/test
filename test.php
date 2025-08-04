<?php

interface CartInterface
{
    public function calcVat();

    public function notify();

    public function makeOrder(float $discount = 1);
}

class Cart implements CartInterface
{
    /** Множитель для нахождения цены с НДС. */
    private const float NDS_PRICE = 1.18;

    /** Множитель для нахождения НДС от цены. */
    private const float NDS_VALUE = 0.18;

    /** Состав корзины. */
    public array $items = [];

    /** Заказ на основе текущей корзины. */
    public ?Order $order = null;

    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    /**
     * Нахождение суммы НДС от `$this->items`.
     *
     * @return float
     */
    public function calcVat(): float
    {
        return $this->computeItemsSum(self::NDS_VALUE);
    }

    /**
     * Уведомление о новом заказе.
     *
     * @return void
     */
    public function notify(): void
    {
        $this->mailer->sendToManagers(sprintf(
            "<p><b>%s</b>%.3f</p>",
            $this->order->id(),
            $this->computeItemsSum()
        ));
    }

    /**
     * Создание заказа.
     *
     * @param float $discount
     * @return void
     */
    public function makeOrder(float $discount = 1): void
    {
        $this->order = new Order($this->items, $this->computeItemsSum(discount: $discount));
        $this->notify();
    }

    /**
     * Высчитывание общей суммы с учетом НДС и скидки.
     *
     * @param float $nds
     * @param float $discount
     * @return float
     */
    private function computeItemsSum(float $nds = self::NDS_PRICE, float $discount = 1): float
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $sum += $item->getPrice() * $nds * $discount;
        }

        return $sum;
    }
}
