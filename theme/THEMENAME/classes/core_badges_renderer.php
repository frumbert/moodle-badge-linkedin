<?php
class theme_boost_campus_extra_core_badges_renderer extends \core_badges_renderer {

    const LINKEDIN_ORGANISATION = "modify this string to match your organisation's ID";

   /**
     * Render an issued badge.
     *
     * @param \core_badges\output\issued_badge $ibadge
     * @return string
     */
    protected function render_issued_badge(\core_badges\output\issued_badge $ibadge) {
        global $USER, $CFG, $DB, $SITE;
        $issued = $ibadge->issued;
        $userinfo = $ibadge->recipient;
        $badgeclass = $ibadge->badgeclass;
        $badge = new \badge($ibadge->badgeid);
        $now = time();
        if (isset($issued['expires'])) {
            if (!is_numeric($issued['expires'])) {
                $issued['expires'] = strtotime($issued['expires']);
            }
            $expiration = $issued['expires'];
        } else {
            $expiration = $now + 86400;
        }

        $badgeimage = is_array($badgeclass['image']) ? $badgeclass['image']['id'] : $badgeclass['image'];
        $languages = get_string_manager()->get_list_of_languages();

        $output = '';
        $output .= \html_writer::start_tag('div', array('id' => 'badge'));
        $output .= \html_writer::start_tag('div', array('id' => 'badge-image'));
        $output .= \html_writer::empty_tag('img', array('src' => $badgeimage, 'alt' => $badge->imagecaption, 'width' => '100'));
        if ($expiration < $now) {
            $output .= $this->output->pix_icon('i/expired',
            get_string('expireddate', 'badges', userdate($issued['expires'])),
                'moodle',
                array('class' => 'expireimage'));
        }

        if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
            $output .= $this->output->single_button(
                        new \moodle_url('/badges/badge.php', array('hash' => $ibadge->hash, 'bake' => true)),
                        get_string('download'),
                        'POST');
            if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now)
                && $userbackpack = badges_get_user_backpack($USER->id)) {

                if (badges_open_badges_backpack_api($userbackpack->id) == OPEN_BADGES_V2P1) {
                    $assertion = new \moodle_url('/badges/backpack-export.php', array('hash' => $ibadge->hash));
                } else {
                    $assertion = new \moodle_url('/badges/backpack-add.php', array('hash' => $ibadge->hash));
                }

                $attributes = ['class' => 'btn btn-secondary m-1', 'role' => 'button'];
                $tobackpack = \html_writer::link($assertion, get_string('addtobackpack', 'badges'), $attributes);
                $output .= $tobackpack;
            }

            // linkedin share button
            // requires an overridden badge.php in customscripts to handle serving the image directly unauthenticated (dl=1)
            // see https://addtoprofile.linkedin.com/#header2
            $imgurl = rawurlencode((new \moodle_url('/badges/badge.php', array('hash' => $ibadge->hash, 'dl' => true)))->out());
            $imgurl = str_replace(['&amp;','%26amp%3B'], '%26', $imgurl);
            $d = $issued['issuedOn'];
            $lang = current_language(); if ($lang === 'en') { $lang = 'en_US'; } // needs work, as linkedin have its own formats?
            $orgname = rawurlencode(self::LINKEDIN_ORGANISATION); // might need org id, but I don't know it
            if (!is_numeric($d)) $d = strtotime($d);
            $ion_year = userdate($d, '%Y');
            $ion_month = userdate($d, '%m');
            $expiration = '';
            if (isset($issued['expires'])) {
                $d = $issued['expires'];
                if (!is_numeric($d)) $d = strtotime($d);
                $expiration = '&expirationYear=' . userdate($d, '%Y') . '&expirationMonth=' . userdate($d, '%m');
            }
            $output .= \html_writer::start_div('singlebutton', array('id' => 'linkedin-share-button'));
            $output .= \html_writer::link(
                "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&organizationName={$orgname}&name={$badge->name}&issueYear={$ion_year}
&issueMonth={$ion_month}{$expiration}&certUrl={$imgurl}&certId={$ibadge->hash}",
                \html_writer::empty_tag('img', ['src' => "https://download.linkedin.com/desktop/add2profile/buttons/{$lang}.png", 'alt' => 'LinkedIn Add to Profile button']),
['target' => '_blank'],
            );
            $output .= \html_writer::end_div();

        }
        $output .= \html_writer::end_tag('div');

        $output .= \html_writer::start_tag('div', array('id' => 'badge-details'));
        // Recipient information.
        $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
        $dl = array();
        if ($userinfo->deleted) {
            $strdata = new \stdClass();
            $strdata->user = fullname($userinfo);
            $strdata->site = format_string($SITE->fullname, true, array('context' => \context_system::instance()));

            $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
        } else {
            $dl[get_string('name')] = fullname($userinfo);
        }
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('issuername', 'badges')] = format_string($badge->issuername, true,
            ['context' => \context_system::instance()]);

        if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
            $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
        }
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('name')] = $badge->name;
        if (!empty($badge->version)) {
            $dl[get_string('version', 'badges')] = $badge->version;
        }
        if (!empty($badge->language)) {
            $dl[get_string('language')] = $languages[$badge->language];
        }
        $dl[get_string('description', 'badges')] = $badge->description;
        if (!empty($badge->imageauthorname)) {
            $dl[get_string('imageauthorname', 'badges')] = $badge->imageauthorname;
        }
        if (!empty($badge->imageauthoremail)) {
            $dl[get_string('imageauthoremail', 'badges')] =
                    \html_writer::tag('a', $badge->imageauthoremail, array('href' => 'mailto:' . $badge->imageauthoremail));
        }
        if (!empty($badge->imageauthorurl)) {
            $dl[get_string('imageauthorurl', 'badges')] =
                    \html_writer::link($badge->imageauthorurl, $badge->imageauthorurl, array('target' => '_blank'));
        }
        if (!empty($badge->imagecaption)) {
            $dl[get_string('imagecaption', 'badges')] = $badge->imagecaption;
        }

        if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
            $coursename = $DB->get_field('course', 'fullname', array('id' => $badge->courseid));
            $dl[get_string('course')] = format_string($coursename, true, ['context' => \context_course::instance($badge->courseid)]);
        }
        $dl[get_string('bcriteria', 'badges')] = self::print_badge_criteria($badge);
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
        $dl = array();
        if (!is_numeric($issued['issuedOn'])) {
            $issued['issuedOn'] = strtotime($issued['issuedOn']);
        }
        $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
        if (isset($issued['expires'])) {
            if ($issued['expires'] < $now) {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']) . get_string('warnexpired', 'badges');

            } else {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
            }
        }

        // Print evidence.
        $agg = $badge->get_aggregation_methods();
        $evidence = $badge->get_criteria_completions($userinfo->id);
        $eids = array_map(function($o) {
            return $o->critid;
        }, $evidence);
        unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

        $items = array();
        foreach ($badge->criteria as $type => $c) {
            if (in_array($c->id, $eids)) {
                if (count($c->params) == 1) {
                    $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
                } else {
                    $items[] = get_string('criteria_descr_' . $type , 'badges',
                            core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
                }
            }
        }

        $dl[get_string('evidence', 'badges')] = get_string('completioninfo', 'badges') . \html_writer::alist($items, array(), 'ul');
        $output .= $this->definition_list($dl);
        $endorsement = $badge->get_endorsement();
        if (!empty($endorsement)) {
            $output .= self::print_badge_endorsement($badge);
        }

        $relatedbadges = $badge->get_related_badges(true);
        $items = array();
        foreach ($relatedbadges as $related) {
            $relatedurl = new \moodle_url('/badges/overview.php', array('id' => $related->id));
            $items[] = \html_writer::link($relatedurl->out(), $related->name, array('target' => '_blank'));
        }
        if (!empty($items)) {
            $output .= $this->heading(get_string('relatedbages', 'badges'), 3);
            $output .= \html_writer::alist($items, array(), 'ul');
        }

        $alignments = $badge->get_alignments();
        if (!empty($alignments)) {
            $output .= $this->heading(get_string('alignment', 'badges'), 3);
            $items = array();
            foreach ($alignments as $alignment) {
                $items[] = \html_writer::link($alignment->targeturl, $alignment->targetname, array('target' => '_blank'));
            }
            $output .= \html_writer::alist($items, array(), 'ul');
        }
        $output .= \html_writer::end_tag('div');

        return $output;
    }
}