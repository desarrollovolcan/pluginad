<?php

namespace MAD\AliExpress;

interface ConnectorInterface {
    public function testConnection(): array;

    public function getProduct( string $productIdOrUrl ): array;

    public function searchProducts( string $keyword ): array;

    public function importProduct( array $productData ): array;

    public function syncProduct( string $aeProductId ): array;

    public function createOrder( array $payload ): array;

    public function getOrder( string $aeOrderId ): array;
}
