<?php

namespace VanOns\Laraberg\Blocks;

use VanOns\Laraberg\Services\OEmbedService;

class Block
{
    /**
     * @var string
     */
    public string $blockName;
    /**
     * @var array<mixed>
     */
    public array $attributes;
    /**
     * @var array<mixed>
     */
    public array $innerBlocks;
    /**
     * @var string
     */
    public string $innerHTML;
    /**
     * @var array<mixed>
     */
    public array $innerContent;

    /**
     * @var BlockTypeRegistry
     */
    protected $registry;

    /**
     * @var OEmbedService
     */
    protected $embedService;

    public function __construct(
        string $blockName,
        array $attributes = [],
        array $innerBlocks = [],
        string $innerHTML = '',
        array $innerContent = []
    ) {
        $this->blockName   = $blockName;
        $this->attributes  = $attributes;
        $this->innerBlocks = $innerBlocks;
        $this->innerHTML = $innerHTML;
        $this->innerContent = $innerContent;

        $this->registry = app('laraberg.registry');
        $this->embedService = app('laraberg.embed');
    }

    public function render(object $context): string
    {
        $output = '';

        $index = 0;
        foreach ($this->innerContent as $innerContent) {
            $output .= is_string($innerContent)
                ? $innerContent
                : $this->innerBlocks[$index++]->render($context);
        }

        $blockType = $this->registry->getBlockType($this->blockName);
        if ($blockType && $blockType->isDynamic()) {
            $output = call_user_func(
                $blockType->renderCallback,
                $this->attributes,
                $output,
                $this,
                $context
            );
        }

        if (strpos($this->blockName, 'embed') !== false) {
            $output = $this->embed($output);
        }

        return $output;
    }

    public function embed(string $content): string
    {
        $embed = $this->embedService->parse($this->attributes['url']);

        return str_replace(
            htmlspecialchars($this->attributes['url']),
            $embed['html'],
            $content
        );
    }

    public static function fromArray(array $args): Block
    {
        $innerBlocks = [];
        foreach ($args['innerBlocks'] ?? [] as $innerBlock) {
            $innerBlocks[] = static::fromArray($innerBlock);
        }

        return new static(
            $args['blockName'] ?? '',
            $args['attrs'] ?? [],
            $innerBlocks,
            $args['innerHTML'] ?? '',
            $args['innerContent'] ?? []
        );
    }
}
