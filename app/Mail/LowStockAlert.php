<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $product;
    public $currentStock;

    /**
     * Create a new message instance.
     *
     * @param Product $product
     * @param int|float $currentStock
     */
    public function __construct(Product $product, $currentStock)
    {
        $this->product = $product;
        $this->currentStock = $currentStock;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('ALERTA: Stock Crítico - SKU: ' . $this->product->sku)
                    ->view('emails.low_stock');
    }
}