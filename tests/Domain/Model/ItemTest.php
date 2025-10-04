<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Domain\Model\Item;

class ItemTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $item = new Item('A1', 'Water', 1.25, 5);
        $this->assertSame('A1', $item->selector);
        $this->assertSame('Water', $item->name);
        $this->assertSame(1.25, $item->price);
        $this->assertSame(5, $item->count);
    }

    public function testValidateItemDataValid(): void
    {
        $this->assertNull(Item::validateItemData('Juice', 2.00, 3));
    }

    public function testValidateItemDataNegativePrice(): void
    {
        $this->assertSame("Item price for 'Soda' cannot be negative", Item::validateItemData('Soda', -1.00, 2));
    }

    public function testValidateItemDataNegativeCount(): void
    {
        $this->assertSame("Item count for 'Water' cannot be negative", Item::validateItemData('Water', 1.00, -2));
    }

    public function testValidateItemDataInvalidName(): void
    {
        $this->assertSame("Item type 'Beer' is not allowed", Item::validateItemData('Beer', 1.00, 1));
    }
}

