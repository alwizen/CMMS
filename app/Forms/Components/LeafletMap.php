<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class LeafletMap extends Field
{
    protected string $view = 'forms.components.leaflet-map';

    protected string|Closure $mode = 'marker';

    protected float|Closure $defaultLat = -6.870255717160778;

    protected float|Closure $defaultLng = 109.18670476501677;

    protected int|Closure $defaultZoom = 13;

    protected int|Closure $height = 400;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(true);
    }

    public function mode(string|Closure $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): string
    {
        return $this->evaluate($this->mode);
    }

    public function defaultCenter(float|Closure $lat, float|Closure $lng): static
    {
        $this->defaultLat = $lat;
        $this->defaultLng = $lng;

        return $this;
    }

    public function defaultLat(float|Closure $lat): static
    {
        $this->defaultLat = $lat;

        return $this;
    }

    public function defaultLng(float|Closure $lng): static
    {
        $this->defaultLng = $lng;

        return $this;
    }

    public function getDefaultLat(): float
    {
        return $this->evaluate($this->defaultLat);
    }

    public function getDefaultLng(): float
    {
        return $this->evaluate($this->defaultLng);
    }

    public function defaultZoom(int|Closure $zoom): static
    {
        $this->defaultZoom = $zoom;

        return $this;
    }

    public function getDefaultZoom(): int
    {
        return $this->evaluate($this->defaultZoom);
    }

    public function height(int|Closure $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->evaluate($this->height);
    }
}
