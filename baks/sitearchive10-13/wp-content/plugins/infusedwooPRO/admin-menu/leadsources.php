<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h1>Tracking Lead Sources in InfusedWoo</h1>
<hr>
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-20-53-30/Screen-Shot-2014-09-25-20-53-30.png" style="max-width: 100%;" />
<br><br>
Sometimes you need to track where your customers came from. And one way to track this is using the leadsource field in infusionsoft. InfusedWoo actually reads the passed URL variables and see if there is a leadsource information and save this information to customer's leadsource field inside infusionsoft.
<br><br>
So for example your website is http://myshop.com. And let's say you are running a marketing campaign in facebook to send your customers to your woocommerce shop. You set this up by attaching a leadsource URL variable as in http://myshop.com/?leadsource=facebook. When the customer purchases your product, this leadsource value will appear in the customer's leadsource field in infusionsoft. This way, you'll know what marketing campaigns are really working by just using these leadsources.
<br><br>
Aside from "leadsource", InfusedWoo also recognizes utm_source and utm_campaign as these are the variables used when you are running campaigns using google.
<br><br>

<h2>Leadsource Reports in Infusionsoft</h2>

In infusionsoft, you can pull reports to see what leadsources are trending, and / or effectiveness of your leadsources. You can check these reports in infusionsoft:
<br>
<br>Marketing → Reports → Leadsource Trending Report
<br>Marketing → Reports → Leadsource Conversion Report
<br>Marketing → Reports → Leadsources ROI
<br>
