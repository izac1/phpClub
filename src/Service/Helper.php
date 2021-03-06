<?php
namespace phpClub\Service;

use \Doctrine\ORM\EntityManager;
use phpClub\Entity\RefLink;

class Helper
{
    public static function insertChain(EntityManager $em, $forPost, $reference, $depth = 0)
    {
        if ($depth == 0) {
            $reflink = new Reflink();
            $reflink->setPost($forPost);
            $reflink->setReference($forPost);
            $reflink->setDepth($depth);

            $em->persist($reflink);
            $em->flush();
        }

        $references = Validator::validateRefLinks($reference->getComment());

        foreach ($references as $r) {
            $r = $em->getRepository('phpClub\Entity\Post')->find($r);

            if ($r) {
                $reflink = new RefLink();
                $reflink->setPost($forPost);
                $reflink->setReference($r);
                $reflink->setDepth($depth + 1);
                $em->persist($reflink);
                $em->flush();

                $reflink = new RefLink();
                $reflink->setPost($r);
                $reflink->setReference($forPost);
                $reflink->setDepth($depth * -1 - 1);
                $em->persist($reflink);
                $em->flush();

                Helper::insertChain($em, $forPost, $r, $depth + 1);
            }
        }
    }

    public static function getChain($number, EntityManager $em)
    {
        static $chain = [];

        if (!in_array($number, $chain)) {
            $chain[] = $number;

            $links = $em->getRepository('phpClub\Entity\RefLink')->findBy(['post' => $number]);

            foreach ($links as $link) {
                Helper::getChain($link->getReference(), $em);
            }

            $links = $em->getRepository('phpClub\Entity\RefLink')->findBy(['reference' => $number]);

            foreach ($links as $link) {
                Helper::getChain($link->getPost(), $em);
            }
        }

        usort($chain, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return $chain;
    }

    public static function generateSalt()
    {
        $salt = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}'), 0, 44);

        return $salt;
    }

    public static function generateHash($password, $salt)
    {
        return md5($password . $salt);
    }

    public static function generateToken()
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 32);
    }

    public static function getToken()
    {
        return (isset($_COOKIE['token'])) ? $_COOKIE['token'] : self::generateToken();
    }

    public static function getArchiveIconUrl($link)
    {
        if (preg_match("!^https?:\/\/2ch\.hk\/pr\/arch\/\d{4}-\d{2}-\d{2}\/res\/\d+\.html$!", $link)) {
            return '/media/images/2ch.ico';
        }

        if (preg_match("!^https?:\/\/arhivach\.org\/thread\/\d+\/?$!", $link)) {
            return '/media/images/arhivach.ico';
        }
    }

    public static function getCatalogUrl()
    {
        return "https://2ch.hk/pr/catalog.json";
    }

    public static function getThreadUrl($number)
    {
        return "https://2ch.hk/pr/res/{$number}.json";
    }

    public static function getSrcUrl($filepath)
    {
        return "https://2ch.hk{$filepath}";
    }

    public static function getThumbUrl($thumbpath)
    {
        return "https://2ch.hk{$thumbpath}";
    }

    public static function getSrcDirectoryPath($number)
    {
        return __DIR__ . "/../../public/pr/src/{$number}";
    }

    public static function getThumbDirectoryPath($number)
    {
        return __DIR__ . "/../../public/pr/thumb/{$number}";
    }

    public static function getSrcPath($filepath)
    {
        return __DIR__ . "/../../public/{$filepath}";
    }

    public static function getThumbPath($thumbpath)
    {
        return __DIR__ . "/../../public/{$thumbpath}";
    }

    public static function getJsonPath($threadnumber)
    {
        return __DIR__ . "/../../public/json/{$threadnumber}.json";
    }
}
