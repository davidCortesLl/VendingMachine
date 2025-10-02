<?php

declare(strict_types=1);

namespace Infrastructure;

use Domain\Model\VendingMachine;
use Redis;

class RedisVendingMachinePersistence {
    private Redis $redis;
    private string $key;

    public function __construct(string $host = 'redis', int $port = 6379, string $key = 'vending_machine') {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
        $this->key = $key;
    }

    public function load(VendingMachine $default): VendingMachine {
        $data = $this->redis->get($this->key);
        if ($data !== false) {
            $object = unserialize($data);
            if ($object instanceof VendingMachine) {
                return $object;
            }
        }
        return $default;
    }

    public function save(VendingMachine $machine): void {
        $this->redis->set($this->key, serialize($machine));
    }
}
