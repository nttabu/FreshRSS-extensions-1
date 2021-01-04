<?php

class LinkTransformer extends AbstractTransformer {
    public function transform($entry) {

        if (false === $this->isRedditLink($entry)) {
            return $entry;
        }

        if (null === $href = $this->extractOriginalContentLink($entry)) {
            return $entry;
        }

        if (preg_match('#(?P<gfycat>gfycat.com/)(.*/)*(?P<token>[^/\-.]*)#', $href, $matches)) {
            try {
                $jsonResponse = file_get_contents("https://api.gfycat.com/v1/gfycats/{$matches['token']}");
                $arrayResponse = json_decode($jsonResponse, true);
                $videoUrl = $arrayResponse['gfyItem']['mp4Url'];
                if (!empty($videoUrl)) {
                    $entry->_content($this->getModifiedContentLink($entry, $videoUrl));
                }
            } catch (Exception $e) {
                Minz_Log::error("GFYCAT API ERROR - {$href}");
            }
        } elseif (preg_match('#(?P<redgifs>redgifs.com/)(.*/)*(?P<token>[^/\-.]*)#', $href, $matches)) {
            try {
                $jsonResponse = file_get_contents("https://api.redgifs.com/v1/gfycats/{$matches['token']}");
                $arrayResponse = json_decode($jsonResponse, true);
                $videoUrl = $arrayResponse['gfyItem']['mp4Url'];
                if (!empty($videoUrl)) {
                    $entry->_content($this->getModifiedContentLink($entry, $videoUrl));
                }
            } catch (Exception $e) {
                Minz_Log::error("REDGIFS API ERROR - {$href}");
            }
        } elseif (preg_match('#v.redd.it#', $href)) {
            try {
                $jsonResponse = file_get_contents("{$this->extractOriginalCommentsLink($entry)}.json");
                $arrayResponse = json_decode($jsonResponse, true);
                $videoUrl = $arrayResponse[0]['data']['children'][0]['data']['media']['reddit_video']['fallback_url'];
                if (!empty($videoUrl)) {
                    $videoUrl = str_replace('?source=fallback', '', $videoUrl);
                    $entry->_content($this->getModifiedContentLink($entry, $videoUrl));
                }
            } catch (Exception $e) {
                Minz_Log::error("REDDIT API ERROR - {$href}");
            }
        }

        return $entry;
    }

    private function getModifiedContentLink($entry, $link) {
        return preg_replace('#<a href="(?P<href>[^"]*)">\[link\]</a>#', "<a href=\"${link}\">[link]</a>", $entry->content());
    }
}
