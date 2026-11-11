<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seeds the pages the platform links to and the launch checklist requires.
 *
 * The policy text below is a working draft written to be clear and specific,
 * not legal advice. Have a qualified lawyer review every policy page for each
 * country you serve before you launch. Each page is editable in
 * Admin > Pages, so edits never need a code change.
 */
class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $i => $page) {
            StaticPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page + [
                    'sort_order' => $i + 1,
                    'is_active' => true,
                    'show_in_footer' => $page['show_in_footer'] ?? true,
                ]
            );
        }

        Cache::forget('footer_pages');

        $this->command->info('Seeded '.count($this->pages()).' static pages.');
        $this->command->warn('Policy pages are drafts. Have a lawyer review them before launch.');
    }

    protected function pages(): array
    {
        $review = "\n\n> This page is a working draft and is not legal advice. "
            .'It must be reviewed by a qualified lawyer for every country you operate in before launch.';

        return [
            [
                'slug' => 'about',
                'title' => 'About',
                'meta_title' => 'About us',
                'meta_description' => 'Why this platform exists, who it is for, and how we decide what gets published.',
                'content' => <<<'MD'
## Why we exist

General video is dominated by very large platforms. We are not trying to be another one of them. We are building the best place for one specific thing: vlogs that tell a real story, starting with travel, food and everyday life.

## What makes this different

**Every upload is reviewed by a person.** Nothing publishes automatically. That is slower, and it is the point: when you open the feed, someone has already checked that what is in it is worth your time.

**Creators come first.** Creators keep ownership of their work, get a clear revenue share, and always get a reason they can act on if something is not approved.

**It works on a slow connection.** Light pages, small images, no blocking scripts. Most of our audience watches on a phone on mobile data, and the site is built for that first.

## How moderation works

1. A creator submits a vlog. Its status becomes **In review**.
2. A human reviewer checks it against our Community Guidelines, usually within 24 hours.
3. It is published, or it is returned with a specific reason the creator can fix.
4. Anyone signed in can report content after it is live. Urgent reports are looked at immediately.

## Who we are

Update this section with your team, your story and how to reach you. Keep it short and human: this is usually the second page a prospective creator reads after the home page.
MD,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'meta_title' => 'Terms of Service',
                'meta_description' => 'The rules for using the platform, for viewers and for creators.',
                'content' => <<<MD
_Last updated: {$this->today()}_

## 1. Accepting these terms

By creating an account or using the site you agree to these terms. If you do not agree, please do not use the service.

## 2. Your account

You must give accurate information, keep your password secure, and you are responsible for activity on your account. You must be at least 13 years old, or the minimum age in your country if that is higher. We may suspend or close accounts that break these terms.

## 3. Your content stays yours

You keep ownership and copyright in everything you upload. By uploading, you grant us a worldwide, non-exclusive, royalty-free licence to host, store, reproduce, adapt for technical delivery (for example transcoding and thumbnails), display and promote your content on and in connection with the service. This licence ends when you delete the content, except for copies already distributed and for backups we retain for a reasonable period.

## 4. What you may not upload

You may not upload content that: infringes anyone's copyright or other rights; is illegal, hateful, harassing or defamatory; sexualises minors in any way; promotes self-harm, violence or dangerous acts; is spam or deliberately misleading; or breaches our Community Guidelines.

## 5. Review and removal

Uploads are reviewed before publication. We may decline, remove, unpublish or age-restrict content, and we may issue strikes against an account. We aim to always give a specific reason and an appeal route.

## 6. Paid promotions

If you are paid or given anything of value to feature a product or brand, you must disclose it clearly in the vlog and in its description.

## 7. Monetisation and payouts

Revenue share, tips and other earnings are governed by the rates published in your creator dashboard at the time the earning is recorded. Payouts are made on a fixed schedule once the minimum balance is reached and your payout and tax details have been verified. We may withhold payment where we reasonably suspect fraud or a breach of these terms.

## 8. Advertising

We may display advertising alongside content, including on pages showing your content.

## 9. Service availability

We work hard to keep the service available but we do not guarantee uninterrupted access. We may change, suspend or discontinue features.

## 10. Our liability

To the extent permitted by law, we are not liable for indirect or consequential loss, lost profits, or loss of data. Nothing in these terms limits liability that cannot be limited by law.

## 11. Ending the agreement

You may delete your account at any time from your profile settings. We may suspend or terminate access if you breach these terms, with notice where reasonable.

## 12. Governing law and disputes

Specify the governing law and the courts or arbitration process that apply, and how a user raises a complaint before going to court.

## 13. Changes

We will post material changes on this page and, where the change is significant, notify registered users by email.

## 14. Contact

Questions about these terms: use the contact page.{$review}
MD,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'What data we collect, why we collect it, and the control you have over it.',
                'content' => <<<MD
_Last updated: {$this->today()}_

## What we collect

**You give us:** name, email address, password (stored only as a secure hash), and optionally a username, avatar, bio and channel details. Creators also provide payout and tax details when they request a payout.

**We collect automatically:** which vlogs you watch and how far through, likes, saves, follows, comments, searches, your IP address, browser and device type, and the pages you visit.

**We do not collect:** payment card numbers. If and when payments are enabled, these are handled by a regulated payment provider and never reach our servers.

## Why we use it

- To run the service: show your library, your history, resume playback, and send the notifications you asked for.
- To keep the platform safe: detect spam and abuse, handle reports, and enforce our guidelines.
- To improve the product: aggregated, non-identifying statistics about what people watch and search for.
- To pay creators: calculate earnings and make payouts.

## Legal basis

Where the law requires a legal basis, we rely on performance of our contract with you (running the service), our legitimate interests (safety, product improvement), your consent (optional cookies and marketing email), and legal obligations.

## Who we share it with

Service providers who help us run the platform (hosting, email delivery, error monitoring, and a payment provider when payments are live), each under contract and only for that purpose. We also share information where we are legally required to, or to protect someone's safety. We do not sell your personal data.

## How long we keep it

Account data: while your account exists. Raw view and search logs: pruned after 90 days; only aggregated, non-identifying statistics are kept after that. Content you publish: until you delete it. Moderation records: kept longer where we need them to enforce our policies.

## Your rights

You can, from your profile settings: download a copy of your data, correct your details, manage notification preferences, and delete your account permanently. You may also ask us to restrict or object to certain processing, and you may complain to your data protection authority.

If you are in India, the Digital Personal Data Protection Act 2023 gives you rights of access, correction, erasure and grievance redressal; contact our grievance officer (named on the contact page). If you are in the EU or UK, the GDPR gives you equivalent rights.

## Children

The service is not intended for children under 13 (or the higher minimum age in your country). We remove accounts we learn belong to children below that age.

## Cookies

See the cookie notice for what we set and why.

## Security

Passwords are hashed, traffic is served over HTTPS, administrative actions are logged, and access to production data is limited to the people who need it. No system is perfectly secure; if a breach affects you we will notify you and the relevant authority as the law requires.

## Contact

Use the contact page, or write to our grievance officer for data protection matters.{$review}
MD,
            ],
            [
                'slug' => 'community-guidelines',
                'title' => 'Community Guidelines',
                'meta_title' => 'Community Guidelines',
                'meta_description' => 'What is welcome here, what is not, and what happens when a line is crossed.',
                'content' => <<<'MD'
These guidelines apply to vlogs, thumbnails, titles, descriptions, comments and channel pages.

## Always not allowed

- **Child safety.** Any content that sexualises, endangers or exploits a minor. Zero tolerance: content is removed immediately, the account is terminated, and we escalate to the authorities.
- **Violence and dangerous acts.** Graphic violence for shock value, threats, or challenges likely to cause serious injury.
- **Self-harm.** Content that promotes, instructs or glamorises suicide, self-harm or eating disorders.
- **Hate and harassment.** Attacks on people based on religion, caste, ethnicity, nationality, gender, sexuality, disability or similar. Targeted harassment, doxxing or pile-ons.
- **Sexual content.** Pornography and sexually explicit material.
- **Illegal activity.** Instructions for crime, trade in weapons or drugs, or fraud.
- **Copyright infringement.** Music, footage or images you do not have the right to use.

## Not allowed without context or disclosure

- **Paid promotion** must be labelled clearly, in the vlog and in the description.
- **Health, legal and financial claims** need a credible source and must not be presented as professional advice.
- **AI-generated or heavily manipulated footage** must be disclosed where a viewer could mistake it for real.

## Spam and manipulation

No engagement farming, no misleading titles or thumbnails, no repetitive uploads of the same content, no mass unsolicited links, and no attempts to inflate views, likes or followers.

## Comments

Disagree freely, attack nobody. No slurs, no spam links, no impersonation. Creators can hide or pin comments on their own vlogs, and anyone can report a comment.

## Strikes and appeals

1. **First strike:** a warning, and the content is removed.
2. **Second strike:** a warning, and a temporary limit on uploads.
3. **Third strike:** the account is suspended pending appeal.

Every strike email explains what happened and how to appeal. We answer appeals within 7 days. Strikes expire after 90 days unless we say otherwise.

## Reporting

Use the report button on any vlog or comment. Child safety, violence and self-harm reports are reviewed immediately; everything else within 48 hours. You can track your own reports from your account.
MD,
            ],
            [
                'slug' => 'copyright',
                'title' => 'Copyright and Takedown Policy',
                'meta_title' => 'Copyright and Takedown Policy',
                'meta_description' => 'How to report content that infringes your copyright, and how we handle notices.',
                'content' => <<<'MD'
## Our position

Creators keep the copyright in their own work, and we expect them to upload only what they have the right to upload. We remove infringing content when we are told about it, and we terminate accounts that infringe repeatedly.

## Reporting an infringement

Use the [copyright takedown form](/copyright/takedown). A complete notice includes:

1. Your name, organisation if any, and a contact email address.
2. A description of the work you own.
3. The exact URL of the content you say infringes it.
4. A statement, made in good faith, that the use is not authorised by you, your agent or the law.

Incomplete notices slow everything down, so please be specific.

## What happens next

We aim to review every notice within 48 hours. If we accept it, the content is removed from public view and the creator is told why. If we decline it, we tell you why. Either way you get a reply by email.

## Counter-notice

If your content was removed and you believe that was wrong, reply to the email we sent you with your reasons. If the dispute is genuine and unresolved, we may restore the content and leave the parties to settle it between themselves.

## Repeat infringers

Three upheld copyright notices against an account result in termination.

## Music and stock footage

Most claims we see are about background music. Use licensed or royalty-free tracks and keep the licence receipt: it makes any dispute quick to settle.
MD,
            ],
            [
                'slug' => 'cookies',
                'title' => 'Cookie Notice',
                'meta_title' => 'Cookie Notice',
                'meta_description' => 'The cookies this site sets and what each one does.',
                'content' => <<<'MD'
## Essential cookies

These are always set, because the site cannot work without them.

| Cookie | Purpose | Lifetime |
| --- | --- | --- |
| Session cookie | Keeps you signed in and ties your requests together | Until you sign out, or 2 hours of inactivity |
| CSRF token | Stops other sites submitting forms as you | Session |
| Cookie choice | Remembers the choice you made here | 1 year |

## Optional cookies

Analytics cookies are only set if you agree. They help us understand which pages are slow or confusing. You can change your mind at any time from the cookie banner.

## Local storage

We use your browser's local storage for small conveniences: your theme choice, your place in a vlog you have not finished, and which filters you last used. This never leaves your device.

## Managing cookies

Your browser can block or delete cookies. Blocking essential cookies will sign you out and break parts of the site.
MD,
            ],
            [
                'slug' => 'faq',
                'title' => 'FAQ',
                'meta_title' => 'Frequently asked questions',
                'meta_description' => 'Answers about watching, uploading, moderation and your account.',
                'content' => <<<'MD'
The FAQ page has its own layout with structured data for search engines. Edit the questions in `StaticPageController::faq()`, or use this page for anything extra you want to add below them.
MD,
                'show_in_footer' => false,
            ],
        ];
    }

    protected function today(): string
    {
        return now()->format('j F Y');
    }
}
