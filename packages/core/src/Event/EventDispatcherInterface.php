<?php

namespace Starship\Core\Event;

/**
 * 轻量封装 Laravel Event，便于插件/模块只依赖 Core 接口。
 */
interface EventDispatcherInterface
{
    /**
     * 派发事件对象；监听者可按引用修改事件属性（如可变 description）。
     */
    public function dispatch(object $event): object;
}
