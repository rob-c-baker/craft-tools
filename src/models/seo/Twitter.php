<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use craft\helpers\Html;
use Override;

class Twitter extends Container
{
    private string $card = 'summary';
    private string $site = '';
    private string $site_id = '';
    private string $creator = '';
    private string $creator_id = '';
    private string $description = '';
    private string $title = '';
    private string $image = '';
    private string $image_alt = '';
    private string $player = '';
    private ?int $player_width = null;
    private ?int $player_height = null;
    private string $player_stream = '';


    #[Override]
    public function render(): string
    {
        $html = [];
        if ($this->card) {
            $html[] = '<meta name="twitter:card" content="' . Html::encode($this->card) . '">';
        }
        if ($this->site) {
            $html[] = '<meta name="twitter:site" content="' . Html::encode($this->site) . '">';
        }
        if ($this->site_id) {
            $html[] = '<meta name="twitter:site:id" content="' . Html::encode($this->site_id) . '">';
        }
        if ($this->creator) {
            $html[] = '<meta name="twitter:creator" content="' . Html::encode($this->creator) . '">';
        }
        if ($this->creator_id) {
            $html[] = '<meta name="twitter:creator:id" content="' . Html::encode($this->creator_id) . '">';
        }
        if ($this->description) {
            $html[] = '<meta name="twitter:description" content="' . Html::encode($this->description) . '">';
        }
        if ($this->title) {
            $html[] = '<meta name="twitter:title" content="' . Html::encode($this->title) . '">';
        }
        if ($this->image) {
            $html[] = '<meta name="twitter:image" content="' . Html::encode($this->image) . '">';
        }
        if ($this->image_alt) {
            $html[] = '<meta name="twitter:image:alt" content="' . Html::encode($this->image_alt) . '">';
        }
        if ($this->player) {
            $html[] = '<meta name="twitter:player" content="' . Html::encode($this->player) . '">';
        }
        if ($this->player_width) {
            $html[] = '<meta name="twitter:player:width" content="' . Html::encode($this->player_width) . '">';
        }
        if ($this->player_height) {
            $html[] = '<meta name="twitter:player:height" content="' . Html::encode($this->player_height) . '">';
        }
        if ($this->player_stream) {
            $html[] = '<meta name="twitter:player:stream" content="' . Html::encode($this->player_stream) . '">';
        }
        return implode("\n", $html);
    }

    #[Override]
    public function fields(): array
    {
        return [
            'card',
            'site',
            'site_id',
            'creator',
            'creator_id',
            'description',
            'title',
            'image',
            'image_alt',
            'player',
            'player_width',
            'player_height',
            'player_stream'
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'card',
            'site',
            'site_id',
            'creator',
            'creator_id',
            'description',
            'title',
            'image',
            'image_alt',
            'player',
            'player_width',
            'player_height',
            'player_stream'
        ];
    }

    #[Override]
    public function headers(): array
    {
        return [];
    }

    public function setCard(string $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function setSite(string $site): static
    {
        $this->site = $site;
        return $this;
    }

    public function setSiteId(string $site_id): static
    {
        $this->site_id = $site_id;
        return $this;
    }

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function setCreatorId(string $creator_id): static
    {
        $this->creator_id = $creator_id;
        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function setImageAlt(string $image_alt): static
    {
        $this->image_alt = $image_alt;
        return $this;
    }

    public function setPlayer(string $player): static
    {
        $this->player = $player;
        return $this;
    }

    public function setPlayerWidth(?int $player_width): static
    {
        $this->player_width = $player_width;
        return $this;
    }

    public function setPlayerHeight(?int $player_height): static
    {
        $this->player_height = $player_height;
        return $this;
    }

    public function setPlayerStream(string $player_stream): static
    {
        $this->player_stream = $player_stream;
        return $this;
    }
}