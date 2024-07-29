<?php
namespace App\Base;
use EasySwoole\Template\RenderInterface;
class ThinkTemplate implements RenderInterface
{
    protected $template;
    public function __construct()
    {
        $this->template = new  \think\Template([
            'view_path'    =>    EASYSWOOLE_ROOT.'/App/Views/',
            'cache_path'    =>    EASYSWOOLE_ROOT.'/Temp/runtime/',
        ]);
    }
    public function render(string $template, array $data = [], array $options = []): ?string
    {
        // TODO: Implement render() method.
        ob_start();
        $this->template->assign($data);
        $this->template->fetch($template);
        $content = ob_get_contents() ;
        return $content;
    }
    public function afterRender(?string $result, string $template, array $data = [], array $options = [])
    {
        // TODO: Implement afterRender() method.
    }

    public function onException(\Throwable $throwable): string
    {
        // TODO: Implement onException() method.
        $msg = "{$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}";
        trigger_error($msg);
        return $msg;
    }
}