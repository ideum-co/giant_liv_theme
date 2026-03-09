<?php

namespace Nwdthemes\Revslider\Plugin;

use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\Design\FileResolution\Fallback\ResolverInterface;
use Magento\Framework\View\Design\Fallback\RulePool;

class TemplateFilePlugin
{
    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * Constructor
     *
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Around getFile
     *
     * @param mixed $interceptor
     * @param callable $proceed
     * @param string $area
     * @param ThemeInterface $themeModel
     * @param string $file
     * @param string|null $module
     * @return string|bool
     */
    public function aroundGetFile(
        $interceptor,
        callable $proceed,
        $area,
        ThemeInterface $themeModel,
        $file,
        $module = null
    ) {
        if ($module === "Nwdthemes_Revslider") {
            $file = $this->resolver->resolve(RulePool::TYPE_TEMPLATE_FILE, $file, $area, $themeModel, null, $module);
        } else {
            $file = $proceed($area, $themeModel, $file, $module);
        }
        return $file;
    }

}
