<?php
namespace Mgufrone\HealthcheckBundle;

class Healthcheck
{
    private function packageInfo() {
        return json_decode(file_get_contents(getcwd()."/composer.json"), true);
    }
    public function deps($info): array {
        return $info['required'];
    }
    public function health(): array {
        $info = $this->packageInfo();
        $version = $info['version'];
        $deps = $this->deps($info);
        return ['status' => 'ok', 'deps'=>$deps, 'version'=>$version];
    }
}
