<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/6
 * Time: 11:17
 */

namespace app\components\socket\Session;


class VirtualSessionStorage extends \Ratchet\Session\Storage\VirtualSessionStorage
{
    protected function loadSession(array &$session = null)
    {
        if (null === $session) {
            $session = &$_SESSION;
        }

        $bags = array_merge($this->bags, [$this->metadataBag]);

        foreach ($bags as $bag) {
            $key = $bag->getStorageKey();
            $session[$key] = $session;
//            $session[$key] = isset($session[$key]) && \is_array($session[$key]) ? $session[$key] : [];
            $bag->initialize($session[$key]);
        }

        $this->started = true;
        $this->closed = false;
    }
}