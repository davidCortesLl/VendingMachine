<?php

declare(strict_types=1);

namespace Infrastructure;

use Domain\Model\VendingMachine;
use Domain\Repository\VendingMachineRepository;
use Redis;

class RedisVendingMachineRepository implements VendingMachineRepository {
    private Redis $redis;
    private string $key;
    private VendingMachine $default;

    public function __construct(string $host = 'redis', int $port = 6379, string $key = 'vending_machine', ?VendingMachine $default = null, ?Redis $redis = null) {
        if ($redis !== null) {
            $this->redis = $redis;
        } else {
            $this->redis = new Redis();
            $this->redis->connect($host, $port);
        }
        $this->key = $key;
        $this->default = $default ?? new VendingMachine([], []);
    }

    public function get(): VendingMachine {
        $data = $this->redis->get($this->key);
        if ($data !== false) {
            $object = unserialize($data);
            if ($object instanceof VendingMachine) {
                return $object;
            }
        }
        return $this->default;
    }

    public function save(VendingMachine $machine): void {
        $this->redis->set($this->key, serialize($machine));
    }
}
