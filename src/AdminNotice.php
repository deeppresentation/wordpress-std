<?php namespace DP\Wp;

use DP\Std\Html\Element;
use DP\Std\Core\Arr;


class AdminNotice
{
    public static function render_raw_notice($htmlContent, string $type = 'success', string $noticeBEMClass = 'dp-notice', ?string $id = null, string $noticeExtraClass = '')
    {
        $clases = [
            "notice notice-${type}", 
            "is-dismissible",
            $noticeExtraClass
        ];

        $notice = new Element('div', $noticeBEMClass, ['class' => Arr::as_string($clases, ' '), 'id'=>$id], $htmlContent);
        $notice->render();
    }

    public static function render_notice(string $text, string $type = 'success', bool $hasButton = false, string $buttonLink = '', string $buttonClass = '', string $buttonText = '', bool $linkInNewTab = true, string $buttonSubText = '', string $noticeBEMClass = 'dp-notice', ?string $id = null, string $noticeExtraClass = '')
    {
        self::render_raw_notice([
            new Element('p', 'text', null, $text),
                (!$hasButton) ? null : new Element('a', 'button', [
                    'class' => $buttonClass,
                    'href' => $buttonLink,
                    'target' => ($linkInNewTab) ? '_blank' : '',
                ], [$buttonText, !empty($buttonSubText) ? new Element('span', 'sub-text', null, $buttonSubText) : null])
            ], 
            $type, $noticeBEMClass, $id, $noticeExtraClass
        );
    }

    public static function render_ask_for_rating_notice(int $activeWeeks, string $productName, string $linkForRating, string $textDomain, string $imgUrl = null, string $imgAlt = 'Dp Intro Tour Rating Image')
    {
        $plugin_name = '<strong>' . $productName . '</strong>';
        $weeksText = sprintf(esc_html(_n("%d week", "%d weeks", $activeWeeks,$textDomain)), $activeWeeks);
        $text = sprintf(__("Awesome, you've been using %s for more then %s. We would be happy and we would really appreciate Your help via rating system at WordPress.org. Nice rating helps us with further development and support of this free version and it takes just one minute. Thank You:)<br>"
            . "<strong>Your DeepPresentation Team</strong>", $textDomain), $plugin_name, $weeksText);


            AdminNotice::render_raw_notice(\array_filter([
                ($imgUrl) ? new Element('img', 'img', ['src' => $imgUrl, 'alt' => $imgAlt, 'class' => 'dp-intro-tour-a4r-5star-img'], null, null, false) : null,
                new Element('p', 'text', null, $text),
                new Element('a', 'link', ['href' => $linkForRating, 'target' => '_blank', 'id'=>'a4r-link-OK'], __('OK, you deserved it', $textDomain)),
                new Element('a', 'link', ['href' => '#', 'id'=>'a4r-link-already-did'], __('I already did', $textDomain), 'already-did'),
                new Element('a', 'link', ['href' => '#', 'id'=>'a4r-link-no-good'], __('No, not good enough', $textDomain), 'no-good')
            ]),
            'success', 'dp-notice', 'a4r-notice'
        );
    }

}