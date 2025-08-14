<?php

namespace ChordPro;

class HtmlFormatter extends Formatter implements FormatterInterface {

    private $sharp_symbol = '&#9839;'; // ♯
    private $natural_symbol = '&#9838;'; // ♮
    private $flat_symbol = '&#9837;'; //

    public function format(Song $song, array $options): string
    {
        $this->setOptions($song,$options);

        $html = '';
        foreach ($song->lines as $line) {
            if (null === $line) {
                $html .= '<br />';
                continue;
            }

            $html .= $this->getLineHtml($line);
        }
        return $html;
    }

    private function getLineHtml(Line $line)
    {
        if ($line instanceof Metadata) {
            return $this->getMetadataHtml($line);
        }

        if ($line instanceof Lyrics) {
            return (true === $this->no_chords) ? $this->getLyricsOnlyHtml($line) : $this->getLyricsHtml($line);
        }
    }

    private function blankChars($text)
    {
        return $text;
    }

    private function getMetadataHtml(Metadata $metadata)
    {
        $openSectionTag = '<div class="chordpro-section">';
        $closeSectionTag = '</div>';
        switch ($metadata->getName()) {
            case 'start_of_chorus':
                $content = (null !== $metadata->getValue())
                    ? '<div class="chordpro-chorus-comment">' . $metadata->getValue() . '</div>'
                    : '';
                return $openSectionTag . $content . '<div class="chordpro-chorus">';
                break;
            case 'end_of_chorus':
                return '</div>' . $closeSectionTag;
                break;
            default:
                $content = '';
                if (strpos($metadata->getName(), 'start_of_') !== false) {
                    $content .= $openSectionTag;
                }
                $content .= '<div class="chordpro-' . $metadata->getName() . '">'
                    . '<span class="chordpro-label">' . $metadata->getLabel() . '</span>'
                    . $metadata->getValue()
                    . '</div>';
                if (strpos($metadata->getName(), 'end_of_') !== false) {
                    $content .= $closeSectionTag;
                }
                return $content;
        }
    }

    private function getLyricsHtml(Lyrics $lyrics)
    {
        $verse = '<div class="chordpro-verse">';
        foreach ($lyrics->getBlocks() as $block) {

            $chords = [];

            $sliced_chords = (true === $this->french_chords) ? $block->getFrenchChord() : $block->getChord();
            if (is_array($sliced_chords)) {
                foreach ($sliced_chords as $sliced_chord) {
                    // Test if minor/major presence before slice chord with exposant part
                    if (strtolower($sliced_chord[0]) === 'n') { // No chord chords (eg. N.C.) don't have a superscript
                        $chords[] = $sliced_chord[0].$sliced_chord[1];
                    } else if (strtolower(mb_substr($sliced_chord[1],0,3)) == 'maj') { // major in first position (without alteration)
                        $chords[] = $sliced_chord[0].mb_substr($sliced_chord[1],0,3).'<sup>'.mb_substr($sliced_chord[1],3)
                            .'</sup>';
                    } else if (strtolower(mb_substr($sliced_chord[1],1,3)) == 'maj') { // major in second position (with alteration)
                        $chords[] = $sliced_chord[0].'<sup>'.mb_substr($sliced_chord[1],0,1).'</sup>'
                            .mb_substr($sliced_chord[1],1,3).'<sup>'.mb_substr($sliced_chord[1],4).'</sup>';
                    } else if (strtolower(mb_substr($sliced_chord[1],0,1)) == 'm') { // minor in first position (without alteration)
                        $chords[] = $sliced_chord[0].mb_substr($sliced_chord[1],0,1).'<sup>'.mb_substr($sliced_chord[1],1)
                            .'</sup>';
                    }
                    else if (strtolower(mb_substr($sliced_chord[1],1,1)) == 'm') { // minor in second position (with alteration)
                        $chords[] = $sliced_chord[0].'<sup>'.mb_substr($sliced_chord[1],0,1).'</sup>'
                            .mb_substr($sliced_chord[1],1,1).'<sup>'.mb_substr($sliced_chord[1],2).'</sup>';
                    }
                    else {
                        $chords[] = $sliced_chord[0].'<sup>'.mb_substr($sliced_chord[1],0).'</sup>';
                    }
                }
            }

            $chord = implode('/',$chords);

            $chord = $this->blankChars(str_replace(
                ['#','b','K'],
                [$this->sharp_symbol,$this->flat_symbol,$this->natural_symbol],
                $chord
                ));
            $text = $this->blankChars($block->getText());

            $verse .= '<span class="chordpro-elem">
              <span class="chordpro-chord">' . $chord . '</span>
              <span class="chordpro-text">' . trim($text ?? '') . '&nbsp;</span>
            </span>';
        }
        $verse .= '</div>';
        return $verse;
    }

    private function getLyricsOnlyHtml(Lyrics $lyrics)
    {
        $verse = '<div class="chordpro-verse">';
        foreach ($lyrics->getBlocks() as $block) {
            $verse .= ltrim($block->getText() ?? '');
        }
        $verse .= '</div>';
        return $verse;
    }
}
