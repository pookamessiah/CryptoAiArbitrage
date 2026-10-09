<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use | Marketplace Policies</title>
    <link rel="icon" href="assets/favicon.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .brand-text { color: #024DDF; }
        .bg-brand { background-color: #024DDF; }
        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 antialiased selection:bg-blue-500 selection:text-white">

    <!-- TOP GLASSMORPHIC NAVIGATION BAR -->
    <header class="sticky top-0 z-40 w-full border-b border-gray-200 bg-white glass-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="flex items-center gap-2">
                    <img src="assets/auth-logo.png" alt="Logo" class="h-8 w-auto object-contain">
                </a>
                <span class="text-xs font-bold bg-gray-100 text-gray-500 uppercase tracking-widest px-2.5 py-1 rounded-md border border-gray-200">Legal Platform</span>
            </div>
            <a href="oauth.php" class="text-sm font-bold text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Return to Account Gateway
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="lg:grid lg:grid-cols-12 lg:gap-10">
            
            <!-- LEFT COLUMN: FLOATING EXTENDED STICKY SIDEBAR -->
            <aside class="hidden lg:block lg:col-span-4 xl:col-span-3">
                <div class="sticky top-24 space-y-8 max-h-[80vh] overflow-y-auto pr-2 pb-6 custom-scrollbar">
                    
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 tracking-wider uppercase mb-3">Our Policies Hub</h3>
                        <nav class="space-y-1">
                            <a href="#" class="block px-3 py-2 text-sm font-semibold text-gray-900 bg-gray-100 rounded-lg transition">Terms of Use Home</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Purchase Policies</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Reseller Policy</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Privacy Policy</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Transfer Recipient Policy</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Travel & Experiences Policy</a>
                            <a href="#" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition">Installment Plan Policy</a>
                        </nav>
                    </div>

                    <hr class="border-gray-200">

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 tracking-wider uppercase mb-3">On This Page</h3>
                        <nav class="space-y-1">
                            <?php
                            $sections = [
                                "overview" => "Overview",
                                "sec1" => "1. Contractual Relationship",
                                "sec2" => "2. Other Policies Integration",
                                "sec3" => "3. Account Provisioning",
                                "sec4" => "4. Ownership of Content",
                                "sec5" => "5. User Contributed Submissions",
                                "sec6" => "6. Marketplace Code of Conduct",
                                "sec7" => "7. Contractual Termination",
                                "sec8" => "8. Disclaimers and Releases",
                                "sec9" => "9. Extended Limitations of Liability",
                                "sec10" => "10. Indemnification Obligations",
                                "sec11" => "11. General Dispositions",
                                "sec12" => "12. Severability",
                                "sec13" => "13. Mobile Automated Messaging",
                                "sec14" => "14. Disputes and Mandatory Arbitration"
                            ];
                            foreach($sections as $id => $title) {
                                echo "<a href='#$id' class='block px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-blue-600 transition truncate'>$title</a>";
                            }
                            ?>
                        </nav>
                    </div>

                    <a href="javascript:window.print()" class="inline-flex items-center gap-2 text-xs font-bold text-brand-text hover:underline px-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Download Print Layout (PDF)
                    </a>
                </div>
            </aside>

            <!-- RIGHT COLUMN: MAIN LEGAL DOCUMENT WRAPPER -->
            <div class="col-span-12 lg:col-span-8 xl:col-span-9 space-y-12 bg-white border border-gray-200 rounded-3xl p-6 sm:p-10 shadow-sm">
                
                <!-- HEADER INFORMATION AREA -->
                <div class="border-b border-gray-100 pb-6">
                    <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight uppercase">Terms Of Use</h1>
                    <div class="mt-4 flex flex-wrap gap-4 items-center text-xs text-gray-500 font-medium">
                        <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full font-semibold">Effective Date: August 12, 2025</span>
                        <span class="hidden sm:inline">&bull;</span>
                        <a href="#" class="text-blue-600 hover:underline">Download Prior Version Archive</a>
                    </div>
                </div>

                <!-- OVERVIEW / WARNINGS BANNER BLOCK -->
                <section id="overview" class="scroll-mt-20 space-y-4">
                    <h2 class="text-xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3">Overview Notice</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        These Terms of Use (“Terms”) govern your use of Live Nation and Ticketmaster’s websites and applications—including (without limitation) <strong>livenation.com</strong>, <strong>ticketmaster.com</strong>, and <strong>ticketexchangebyticketmaster.com</strong>—and your purchase, possession, sale, acceptance, or use of any of our tickets, products, or services (our “Marketplace,” defined in Section 1, below).
                    </p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        Our other policies—including our Standard Purchase Policy, Resale Purchase Policy, Travel & Experiences Policy, Transfer Recipient Policy, Reseller Policy, and Privacy Policy (collectively “Other Policies”)—are also incorporated into these Terms.
                    </p>

                    <!-- NOTICE BOX ARBITRATION CALLOUT -->
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3 shadow-inner">
                        <h4 class="text-xs font-black tracking-wider text-amber-800 uppercase flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Notice Regarding Dispute Arbitration &amp; Class Action Waivers
                        </h4>
                        <p class="text-xs text-amber-900 leading-relaxed text-justify font-medium">
                            The Terms contain an arbitration agreement and class action waiver—along with some limited exceptions—in Section 14, below. Specifically, you and we agree that any dispute or claim relating in any way to the Terms, your use of the Marketplace, or products or services sold, distributed, issued, or serviced by us or through us, will be resolved by binding arbitration, rather than in court.
                        </p>
                        <p class="text-xs text-amber-900 leading-relaxed text-justify">
                            This updated arbitration agreement and class action waiver applies to all such disputes or claims between us, except those already filed and currently pending as of August 12, 2025. By agreeing to arbitration, you and we each waive any right to participate in a class action lawsuit or class action arbitration, except those already filed and currently pending as of August 12, 2025.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 text-xs text-gray-600">
                        <span class="font-bold text-gray-900 uppercase block mb-1">Notice Regarding Future Changes to Terms:</span>
                        From time to time, we may update these Terms and our Other Policies, as detailed in Section 11, below. Any changes we make will only be binding on you if and when you agree to the updated Terms.
                    </div>
                </section>

                <hr class="border-gray-100">

                <!-- SECTION 1 -->
                <section id="sec1" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">1. This is a Contract Between You and Us</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        These Terms are a legally binding agreement between you, the user (“you” or “your”), and us. We use the terms “us,” “we,” and “our” to collectively refer to Ticketmaster LLC (“Ticketmaster”), Live Nation Entertainment, Inc. (“Live Nation”), and all of Ticketmaster and Live Nation’s parents, subsidiaries, and affiliates. A list of Live Nation’s subsidiaries is available in Live Nation’s most recent 10-K report, which is publicly accessible via their IR portal.
                    </p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        “Marketplace” refers to our websites and mobile applications—including (without limitation) livenation.com, ticketmaster.com, and ticketexchangebyticketmaster.com—and to our tickets, products, and services.
                    </p>
                </section>

                <!-- SECTION 2 -->
                <section id="sec2" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">2. Other Policies</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        In addition to these Terms, we have Other Policies that apply to your use of the Marketplace and are incorporated by reference into these Terms—specifically:
                    </p>
                    <ul class="list-disc pl-5 text-sm space-y-2 text-gray-600">
                        <li><strong>The Standard Purchase Policy:</strong> Explains your rights and responsibilities when purchasing standard (primary) tickets and associated products and services on the Marketplace.</li>
                        <li><strong>The Resale Purchase Policy:</strong> Explains your rights and responsibilities when purchasing resale (secondary) tickets and associated products and services on the Marketplace.</li>
                        <li><strong>The Travel &amp; Experiences Policy:</strong> Explains your rights and responsibilities when purchasing a travel experience package on the Marketplace.</li>
                        <li><strong>The Transfer Recipient Policy:</strong> Explains your rights and responsibilities when you accept a Ticket Transfer.</li>
                        <li><strong>The Reseller Policy:</strong> Explains your rights and responsibilities when posting or selling tickets.</li>
                        <li><strong>The Privacy Policy:</strong> Explains how we handle the personal information that we process.</li>
                    </ul>
                </section>

                <!-- SECTION 3 -->
                <section id="sec3" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">3. Accounts</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        To access some of the services on the Marketplace, including to buy or sell tickets or receive a Ticket Transfer, you must create an account. Each account must be linked to a unique individual and contain up-to-date information that is accurate, complete, and verifiable. 
                    </p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        To be eligible for an account, you must be either (1) at least 18 years old (or the age of majority in your jurisdiction of residence, if higher) or (2) at least 13 years old and authorized by your parent or legal guardian to create an account and use the Marketplace. 
                    </p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        You are responsible for keeping your account secure and for protecting your information—don’t share your information or login credentials with others. Accounts with no purchase or transfer activity for eight (8) or more years are considered inactive and may be permanently deleted.
                    </p>
                </section>

                <!-- SECTION 4 -->
                <section id="sec4" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">4. Our Content</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        The Marketplace, including all software, content (other than User Content, as defined below), data, and other materials on the Marketplace (collectively, our “Content”), is owned by us and/or our licensors. We grant you a limited, non-exclusive, non-transferable, non-sublicensable license to use the Marketplace and view our Content, for personal use only, conditioned on your compliance with these Terms.
                    </p>
                </section>

                <!-- SECTION 5 -->
                <section id="sec5" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">5. User Content</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        In connection with your use of the Marketplace, you may be able to post, upload, or submit content to be made available on the Marketplace and viewable by other users (“User Content”). You expressly agree not to post, upload to, transmit, distribute, store, create, or otherwise publish through the Marketplace any User Content that is false, unlawful, misleading, libelous, defamatory, obscene, pornographic, indecent, lewd, suggestive, or harassing.
                    </p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        By posting, uploading, or submitting your User Content to the Marketplace, you grant us a license to access, store, transmit, use, reproduce, create derivative works of, distribute, publicly perform, display, reformat, incorporate into advertisements and other works, promote, archive, and modify your User Content in our sole discretion and for any purpose, in any and all media now or hereafter known. This license is royalty-free, transferable, sub-licensable, assignable, perpetual, worldwide, and irrevocable.
                    </p>
                </section>

                <!-- SECTION 6 -->
                <section id="sec6" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">6. Our Marketplace Code of Conduct</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        You agree that you will not do or attempt any of the following while using any portion of the Marketplace:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-100 rounded-2xl p-5">
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Circumvent access controls or technology.</div>
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Use bot technology or automated software.</div>
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Frame, mirror, scrape, or crawl pages.</div>
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Commit brute force attacks on platform services.</div>
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Order more tickets than event limits permit.</div>
                        <div class="flex items-start gap-2"><span class="text-blue-600 font-bold">&bull;</span> Create duplicate accounts using false details.</div>
                    </div>
                </section>

                <!-- SECTION 7 -->
                <section id="sec7" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">7. Termination</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        We may terminate or suspend your account and/or your access to the Marketplace at any time, for any reason. If we have reason to believe that you violated these Terms or any of our Other Policies, we may prevent you from using the Marketplace, cancel pending orders, and refuse to honor future ticket actions.
                    </p>
                </section>

                <!-- SECTION 8 -->
                <section id="sec8" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">8. Disclaimer of Warranties and Release</h2>
                    <p class="text-sm bg-red-50/50 border border-red-100 rounded-xl p-4 text-justify leading-relaxed text-gray-600 font-medium">
                        WE OFFER OUR MARKETPLACE “AS IS” AND “AS AVAILABLE,” WITHOUT WARRANTIES (UNLESS EXPLICITLY STATED IN OUR OTHER POLICIES). TO THE FULLEST EXTENT PERMISSIBLE BY LAW, WE DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING ANY WARRANTY OF TITLE, NON-INFRINGEMENT, ACCURACY, MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR WARRANTIES THAT MAY ARISE FROM COURSE OF DEALING OR COURSE OF PERFORMANCE OR USAGE OF TRADE.
                    </p>
                </section>

                <!-- SECTION 9 -->
                <section id="sec9" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">9. Limitation of Liability</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        You voluntarily assume all risks incidental to the event for which the ticket is issued (whether occurring before, during, or after the event). You waive any claims for personal injury or death—including (without limitation) as a result of any communicable disease or illness, even if you contract it while attending an event—against us, management, facilities, venues, leagues, artists, promoters, and affiliated entities.
                    </p>
                </section>

                <!-- SECTION 10 -->
                <section id="sec10" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">10. Indemnification</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        You agree to defend, indemnify, and hold us and our officers, directors, employees, agents, Event Organizers, suppliers, advertisers, and sponsors harmless from and against any and all claims, damages, losses and expenses of any kind arising from your misuse of the Marketplace, content violations, or negligence.
                    </p>
                </section>

                <!-- SECTION 11 -->
                <section id="sec11" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">11. General</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        We may update these Terms and our Other Policies from time to time to reflect changes in our Marketplace, for legal, regulatory, or security reasons. If we revise these Terms, we will update the Effective Date at the top. These Terms, and licenses or rights granted herein, may be assigned by us but may not be assigned by you without our prior express written consent.
                    </p>
                </section>

                <!-- SECTION 12 -->
                <section id="sec12" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">12. Severability</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        If any part of these Terms is not valid or enforceable, then that provision shall be deemed severable, meaning it will not affect the validity or enforceability of any remaining provisions.
                    </p>
                </section>

                <!-- SECTION 13 -->
                <section id="sec13" class="scroll-mt-20 space-y-3">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">13. Mobile Messaging</h2>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        We offer browsing and mobile messaging services which may include alerts, promotions, and other marketing offers. If you choose to receive these mobile messages, you agree that we may send recurring messages to the mobile phone number you provided. You may opt out of any messages by replying with the text message “STOP”.
                    </p>
                </section>

                <!-- SECTION 14 -->
                <section id="sec14" class="scroll-mt-20 space-y-4">
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">14. Disputes and Arbitration</h2>
                    <p class="text-sm font-bold text-gray-900 uppercase">Mandatory Arbitration Agreement and Class Action Waiver:</p>
                    <p class="text-sm leading-relaxed text-gray-600 text-justify">
                        THE PARTIES AGREE THAT, EXCEPT AS PROVIDED BELOW, ANY DISPUTE, CLAIM, OR CONTROVERSY RELATING IN ANY WAY TO THE TERMS OR YOUR USE OF THE MARKETPLACE, WHICH INCLUDES ALL PRODUCTS OR SERVICES SOLD, DISTRIBUTED, ISSUED, OR SERVICED BY OR THROUGH US—IRRESPECTIVE OF WHEN THAT DISPUTE, CLAIM, OR CONTROVERSY AROSE—WILL BE RESOLVED SOLELY BY BINDING ARBITRATION AS SET FORTH IN THE TERMS, RATHER THAN IN COURT. 
                    </p>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 space-y-3 text-xs leading-relaxed text-gray-600">
                        <p><strong>Informal Dispute Resolution:</strong> Before a party may commence an arbitration or assert a claim in small claims court, they will engage in an informal dispute resolution process.</p>
                        <p>The party seeking to initiate a claim must give written notice to the other party. To notify us that you intend to initiate informal dispute resolution, you must send an email to Live Nation Entertainment, Inc. at <strong>disputes@ticketmaster.com</strong>, providing: your full name; account email; and a brief description of your claim.</p>
                        <p>The conference shall occur within sixty (60) days of receipt of the written notice, unless an extension is mutually agreed upon by the parties.</p>
                    </div>
                </section>

                <!-- FOOTER BRAND TRADEMARK BLOCK -->
                <div class="border-t border-gray-100 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-gray-400">
                    <div>&copy; 2026 Ticketmaster / Live Nation Entertainment. All rights reserved.</div>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-gray-600 underline">Privacy Settings</a>
                        <a href="#" class="hover:text-gray-600 underline">Contact Legal Counsel</a>
                    </div>
                </div>

            </div>
        </div>
    </main>

</body>
</html>
