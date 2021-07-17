<?php
namespace Mgufrone\HealthcheckBundle;

class Healthcheck
{
    private function packageInfo() {
        $cwd = str_replace(getcwd(), "/public", "");
        return json_decode(file_get_contents($cwd."/composer.json"), true);
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
