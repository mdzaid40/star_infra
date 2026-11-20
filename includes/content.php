<?php
/**
 * Default content.
 *  - Defines every editable setting (used to build Admin → Settings).
 *  - Seeds the database on install.
 *  - Acts as a fallback when the database is unavailable, so the site never shows a blank page.
 *
 * Text fields marked "rich" support two simple formatting shortcuts:
 *   *text*   → italic accent (the serif style used in headings)
 *   new line → line break
 */
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

function settings_schema(): array
{
    return [
        'General' => [
            'site_name'        => ['label' => 'Company name', 'type' => 'text', 'default' => SITE_NAME],
            'site_tagline'     => ['label' => 'Top bar tagline', 'type' => 'text', 'default' => SITE_TAGLINE],
            'footer_about'     => ['label' => 'Footer description', 'type' => 'textarea', 'default' => 'Practical engineering and infrastructure support for industrial, utility and construction projects.'],
            'footer_slogan'    => ['label' => 'Footer slogan', 'type' => 'text', 'default' => 'Engineering with purpose. Delivering with care.'],
        ],
        'Contact details' => [
            'contact_email'    => ['label' => 'Public email', 'type' => 'email', 'default' => SITE_EMAIL],
            'contact_phone'    => ['label' => 'Phone number', 'type' => 'text', 'default' => SITE_PHONE],
            'contact_whatsapp' => ['label' => 'WhatsApp number (digits with country code, optional)', 'type' => 'text', 'default' => ''],
            'contact_address'  => ['label' => 'Address', 'type' => 'textarea', 'default' => SITE_ADDRESS],
            'map_embed_url'    => ['label' => 'Google Maps embed URL (optional)', 'type' => 'url', 'default' => '', 'help' => 'Google Maps → Share → Embed a map → copy only the src="…" link.'],
            'notify_email'     => ['label' => 'Send contact-form enquiries to', 'type' => 'email', 'default' => SITE_EMAIL, 'help' => 'Leave empty to use the public email.'],
        ],
        'Homepage' => [
            'hero_eyebrow'     => ['label' => 'Hero label', 'type' => 'text', 'default' => 'ENGINEERING & INFRASTRUCTURE PARTNER'],
            'hero_title'       => ['label' => 'Hero heading', 'type' => 'rich', 'default' => "Building the systems\nthat keep *industry moving.*"],
            'hero_text'        => ['label' => 'Hero text', 'type' => 'textarea', 'default' => 'Integrated plant, pipeline, civil and industrial maintenance services — planned carefully, executed responsibly.'],
            'hero_image'       => ['label' => 'Hero background image', 'type' => 'image', 'default' => 'assets/images/plant-team.webp'],
            'intro_title'      => ['label' => '"Who we are" heading', 'type' => 'rich', 'default' => "Grounded in experience.\n*Focused on execution.*"],
            'intro_lead'       => ['label' => '"Who we are" lead paragraph', 'type' => 'textarea', 'default' => 'We bring together the people, processes and technical capabilities needed to deliver demanding industrial and infrastructure work.'],
            'intro_text'       => ['label' => '"Who we are" paragraph', 'type' => 'textarea', 'default' => 'From pipeline installation and mechanical works to civil construction and ongoing maintenance, our approach is built around safe practices, clear coordination and dependable delivery.'],
            'statement_title'  => ['label' => '"How we work" statement', 'type' => 'rich', 'default' => "Good engineering is more than\ngetting the job done. It is doing it\n*the right way.*"],
            'cta_title'        => ['label' => 'Bottom call-to-action heading', 'type' => 'rich', 'default' => "Let's build something\n*that works.*"],
            'home_show_blog'   => ['label' => 'Show latest blog posts on homepage', 'type' => 'bool', 'default' => '1'],
        ],
        'About page' => [
            'about_title'      => ['label' => 'Page heading', 'type' => 'rich', 'default' => "Practical thinking.\n*Solid execution.*"],
            'about_subtitle'   => ['label' => 'Page sub-heading', 'type' => 'textarea', 'default' => 'Engineering and infrastructure services shaped around real project requirements.'],
            'about_lead'       => ['label' => 'Approach – lead paragraph', 'type' => 'textarea', 'default' => 'Our firm undertakes plant and mechanical works, gas pipeline infrastructure, civil construction and industrial maintenance for public and private sector projects.'],
            'about_body'       => ['label' => 'Approach – paragraphs (blank line between paragraphs)', 'type' => 'textarea', 'rows' => 7, 'default' => "We understand that successful project delivery depends on more than technical scope. It requires careful planning, disciplined site coordination, attention to quality, and a strong commitment to safety and statutory compliance.\n\nWe aim to be a dependable contracting partner — responsive to project needs and focused on completing each assignment to its agreed scope and requirements."],
        ],
        'Capabilities page' => [
            'projects_title'   => ['label' => 'Page heading', 'type' => 'rich', 'default' => "Ready for the demands\nof *real-world projects.*"],
            'projects_subtitle'=> ['label' => 'Page sub-heading', 'type' => 'textarea', 'default' => 'Flexible contracting support across tenders, work orders and multi-discipline assignments.'],
            'projects_note'    => ['label' => 'Note below delivery framework', 'type' => 'textarea', 'default' => 'Project photographs, client names, certifications and completed-project references can be added here once verified and approved for publication.'],
        ],
        'SEO & social' => [
            'meta_description' => ['label' => 'Default meta description', 'type' => 'textarea', 'default' => 'Plant, mechanical, gas pipeline, civil construction and industrial maintenance services across India.'],
            'og_image'         => ['label' => 'Default social share image', 'type' => 'image', 'default' => 'assets/images/plant-team.webp'],
            'social_linkedin'  => ['label' => 'LinkedIn URL', 'type' => 'url', 'default' => ''],
            'social_facebook'  => ['label' => 'Facebook URL', 'type' => 'url', 'default' => ''],
            'social_instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'default' => ''],
            'social_youtube'   => ['label' => 'YouTube URL', 'type' => 'url', 'default' => ''],
            'head_scripts'     => ['label' => 'Extra <head> code (Google Analytics / Search Console verification)', 'type' => 'code', 'default' => '', 'help' => 'Pasted as-is into every page. Only paste code from trusted sources.'],
        ],
    ];
}

/** Flat key => default map. */
function settings_defaults(): array
{
    $out = [];
    foreach (settings_schema() as $fields) {
        foreach ($fields as $key => $def) { $out[$key] = (string)$def['default']; }
    }
    return $out;
}

function default_services(): array
{
    return [
        ['slug' => 'mechanical', 'title' => 'Plant & Mechanical Works', 'category' => 'INDUSTRIAL / MECHANICAL', 'icon' => '⌁',
         'short_description' => 'Fabrication, erection, piping, structural works, testing and shutdown support.',
         'description' => 'Execution support for plant installations, upgrades, shutdowns and associated mechanical packages.',
         'features' => "Fabrication and erection\nPiping and structural works\nTanks and vessel works\nHydro testing, maintenance and shutdown works",
         'image' => 'assets/images/plant-team.webp', 'image_alt' => 'Engineers reviewing plans at an industrial plant'],
        ['slug' => 'pipelines', 'title' => 'Steel Gas Pipeline Works', 'category' => 'GAS INFRASTRUCTURE', 'icon' => '⌇',
         'short_description' => 'Welding, NDT coordination, coating, trenching, testing and commissioning.',
         'description' => 'Pipeline construction activities from site preparation through testing, commissioning and handover documentation.',
         'features' => "Welding, stringing and radiographic testing (RT)\nCoating, trenching, lowering and backfilling\nHydro testing and commissioning\nHorizontal directional drilling (HDD)",
         'image' => 'assets/images/steel-pipeline-welding.webp', 'image_alt' => 'Steel gas pipeline welding at a construction site'],
        ['slug' => 'mdpe', 'title' => 'MDPE Pipeline Works', 'category' => 'DISTRIBUTION NETWORKS', 'icon' => '⌘',
         'short_description' => 'Pipeline laying, jointing, testing and associated reinstatement works.',
         'description' => 'Installation and associated works for medium-density polyethylene gas distribution pipelines.',
         'features' => "Trenching and pipeline laying\nJointing and related installation activities\nTesting and commissioning\nBackfilling and associated works",
         'image' => 'assets/images/mdpe-pipeline-trenching.webp', 'image_alt' => 'Pipeline installation in an excavated trench'],
        ['slug' => 'gi', 'title' => 'GI Pipeline Works', 'category' => 'PIPELINE INSTALLATION', 'icon' => '⟷',
         'short_description' => 'Supply, installation, jointing, testing and commissioning of GI lines.',
         'description' => 'Supply and installation services for GI pipeline systems in line with project specifications.',
         'features' => "Supply and material coordination\nLaying and installation\nJointing and testing\nCommissioning support",
         'image' => 'assets/images/gi-pipes.webp', 'image_alt' => 'Industrial steel pipes ready for installation'],
        ['slug' => 'civil', 'title' => 'Civil Construction Works', 'category' => 'CIVIL / CONSTRUCTION', 'icon' => '▥',
         'short_description' => 'Excavation, earthwork, foundations, concreting and road restoration.',
         'description' => 'Supporting infrastructure and industrial projects with essential civil and restoration activities.',
         'features' => "Excavation and earthwork\nConcreting and foundation works\nRoad restoration\nCivil repair and maintenance",
         'image' => 'assets/images/civil-construction.webp', 'image_alt' => 'Civil construction and foundation work'],
        ['slug' => 'maintenance', 'title' => 'Industrial Maintenance Works', 'category' => 'ASSET SUPPORT', 'icon' => '⚙',
         'short_description' => 'Planned and responsive civil, mechanical, plant and pipeline maintenance.',
         'description' => 'Maintenance support across civil, mechanical, plant and gas pipeline infrastructure.',
         'features' => "Routine and corrective maintenance\nPlant and mechanical upkeep\nPipeline maintenance support\nShutdown and allied maintenance activities",
         'image' => 'assets/images/industrial-maintenance.webp', 'image_alt' => 'Technician inspecting industrial pipeline valves'],
    ];
}

function default_gallery(): array
{
    return [
        ['image' => 'assets/images/plant-team.webp', 'caption' => 'Plant & engineering coordination', 'alt_text' => 'Engineering team at industrial facility'],
        ['image' => 'assets/images/steel-pipeline-welding.webp', 'caption' => 'Steel pipeline construction', 'alt_text' => 'Steel pipeline welding'],
        ['image' => 'assets/images/civil-construction.webp', 'caption' => 'Civil & infrastructure works', 'alt_text' => 'Civil foundation construction'],
    ];
}
