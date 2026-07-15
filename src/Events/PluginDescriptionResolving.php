<?php

namespace Starship\Core\Events;

use Starship\Core\Plugin\Plugin;

/**
 * 插件列表/详情组装描述文案时派发；监听者可改 description。
 */
class PluginDescriptionResolving
{
    public function __construct(
        public Plugin $plugin,
        public ?string $description,
    ) {}
}
