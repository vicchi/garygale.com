<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
   <title>Gary Gale - Geotastic&#33;</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="google-site-verification" content="VyibKsAITAnnjrTv9ud4ivpQU1c5VhsZSF3H8nStnlQ">
   <link rel="stylesheet" href="/archive/v1/yui/2.9.0/build/reset-fonts-grids/reset-fonts-grids.css" type="text/css">
	<link rel="stylesheet" href="/archive/v1/yui/2.9.0/build/base/base.css" type="text/css">
	<link rel="stylesheet" href="/archive/v1/css/newstyles.css" type="text/css">
	<link rel="shortcut icon" href="/archive/v1/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/archive/v1/favicon.ico" type="image/x-icon">
	<meta name="keywords" content="gary, gale, garygale, yahoo, ovi, nokia, geo, geotastic, geoinformatics, vicchi, blog, place, location, geolocation">

<?php
	// define the feeds we're pulling content from

	$feeds = array(
		'http://feeds.feedburner.com/www-vicchi-org',
		'http://www.slideshare.net/rss/user/vicchi',
		'http://feeds.delicious.com/v2/rss/vicchi/garygale+clippings',
		'http://feeds.delicious.com/v2/rss/vicchi/garygale+article',
		'http://feeds.delicious.com/v2/rss/vicchi/garygale+videos'
	);

	// assemble the YQL statement
	$yql = 'select category,meta.views,content.thumbnail,content.description,title,'.
		       'link,description from rss where url in ';
	$yql .= "('" . join($feeds,"','") . "')";

	// assemble the request url
	$root = 'http://query.yahooapis.com/v1/public/yql?q=';
	$url = $root . urlencode($yql) . '&format=json';

	// get the feeds and populate the data to echo out in the HTML
	$feeds = renderFeeds($url);
	$blog = $feeds['blog'];

	$videos = $feeds['videos'];
	$articles = $feeds['articles'];
	$decks = $feeds['slides'];
	$mentions = $feeds['mentions'];

	// Grab the conference agenda from my blog
	$yql = 'select * from html where url=' .
        '"http://www.vicchi.org/speaking/"'.
        ' and xpath="//div[@class=\'row\']//ul" limit 1';
	$travels = renderHTML($root.urlencode($yql).'&format=xml&diagnostics=false');

	// btw, diagnostics=false means YQL doesn't send a diagnostics part

	// Function: renderFeeds
	// Loads all the feeds and transforms them into HTML
	function renderFeeds($url) {
		// grab content from YQL via CURL
		$c = getStuff($url);

		// content comes back as JSON so turn it into PHP objects
		$x = json_decode($c);

		// reset counters
		$count = 0;
		$vidcount = 0;
		$blogcount = 0;
		$slidecount = 0;
		$mencount = 0;

		// start a new array to return
		$keys = array('mentions', 'articles', 'slides', 'videos', 'blog');
        $out = array_fill_keys($keys, '');
		$hash = array ();

		// loop over the YQL results, if they exist
		if ($x->query->results->item) {
			foreach ($x->query->results->item as $i) {
				if (array_key_exists($i->link, $hash))
					continue;

                if (property_exists($i, 'category') && property_exists($i->category, 'content')) {
                    if (isset($i->category) && !empty($i->category) && strstr ($i->category->content, 'clippings') && $mencount < 5) {
                        $out['mentions'] .= '<li><h4><a href="' . $i->link . '">' . $i->title .
                                                      '</a></h4><p>' . html_entity_decode($i->description) .
                                                      '</p></li>';
                        $mencount++;
                        $hash["$i->link"] = true;
                    }

                    elseif (isset($i->category) && $i->category && strstr ($i->category->content, 'article') && $count < 5) {
                        $out['articles'] .= '<li><h4><a href="' . $i->link . '">' . $i->title .
                                                  '</a></h4><p>' . html_entity_decode($i->description) .
                                                  '</p></li>';
                        $count++;
                        $hash["$i->link"] = true;
                    }

                    // if the description contains an embed code, add to videos
                    // (I use delicious for video bookmarks and add the embed code
                    // as a description, check here:
                    //         http://feeds.delicious.com/v2/rss/codepo8/myvideos)

                    elseif (strstr($i->description,'embed src') && $vidcount < 4) {
                        $out['videos'] .= '<li><h3><a href="' . $i->link . '">' . $i->title .
                                              '</a></h3><p>' . html_entity_decode($i->description) .
                                              '</p></li>';
                        $vidcount++;
                        $hash["$i->link"] = true;
                    }
                }

                // if the link comes from the blog, add to the blog HTML
                elseif (strstr($i->link,'vicchi.org') && $blogcount < 5) {
                    $out['blog'].= '<li><h4><a href="' .$i->link . '">' .$i->title .
                           '</a></h4><p>'. preg_replace('/&nbsp;/',' ',$i->description) .
                           '</p></li>';
                    $blogcount++;
                    $hash["$i->link"] = true;
                }


                // for slideshare, add to slides element.
                elseif (strstr($i->link,'slideshare') && $slidecount < 4) {
                    $out['slides'] .= '<li><h3><a href="' . $i->link . '">' . $i->title .
                                          '</a></h3><p><a href="' . $i->link .
                                          '"><img src="' . $i->content->thumbnail->url .
                                          '"></a>' . $i->content->description->content .
                                          '</p></li>';
                    $slidecount++;
                    $hash["$i->link"] = true;
                }
			}	// end-foreach
		} // end-if
		// return back the array :)

		//$out['slides'] = preg_replace('/&nbsp;/',' ',$out['slides']);
		//$out['blog'] = preg_replace('/&nbsp;/',' ',$out['blog']);
		//$out['articles'] = preg_replace('/&nbsp;/',' ',$out['articles']);
		//$out['videos'] = preg_replace('/&nbsp;/',' ',$out['videos']);
		//$out['mentions'] = preg_replace('/&nbsp;/',' ',$out['mentions']);

		return $out;
	}

	// Function: getStuff
	// Simple CURL function to get data
	function getStuff($url) {
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);

		if (empty($buffer)) {
			return 'Error retrieving data; please try again later.';
		}

		else {
			return $buffer;
		}
	}

	// Function: renderHTML
	// Quick and dirty formatter for HTML
	function renderHTML($url) {
		// pull the information from the supplied URL
		$c = getStuff($url);

		// check that something actually came back
		if (strstr($c, '<')) {
			// remove all the XML stuff
			$c = preg_replace("/.*<results>|<\/results>.*/",'',$c);
		    $c = preg_replace("/<\?xml version=\"1\.0\"".
		                      " encoding=\"UTF-8\"\?>/",'',$c);
		    // remove all comments
		    $c = preg_replace("//",'',$c);

			// send it all back
			return $c;
		}
	}

	// Function: createMailto
	// Courtesy of Ken Butcher (http://www.melug-central.org/~ken/php/)
	// See http://johnhaller.com/jh/useful_stuff/obfuscate_mailto/code/php/

	function createMailto($strEmail) {
        $strNewAddress = '';
        for ($intCounter = 0; $intCounter < strlen($strEmail); $intCounter++) {
            $strNewAddress .= "&#" . ord(substr($strEmail,$intCounter,1)) . ";";
        }
        $arrEmail = explode("&#64;", $strNewAddress);
        $strTag = "<script language="."Javascript"." type="."text/javascript".">\n";
        $strTag .= "<!--\n";
        $strTag .= "document.write('<a href=\"mai');\n";
        $strTag .= "document.write('lto');\n";
        $strTag .= "document.write(':" . $arrEmail[0] . "');\n";
        $strTag .= "document.write('@');\n";
        $strTag .= "document.write('" . $arrEmail[1] . "\">');\n";
        $strTag .= "document.write('" . $arrEmail[0] . "');\n";
        $strTag .= "document.write('@');\n";
        $strTag .= "document.write('" . $arrEmail[1] . "<\/a>');\n";
        $strTag .= "// -->\n";
        $strTag .= "</script><noscript>" . $arrEmail[0] . " at \n";
        $strTag .= str_replace("&#46;"," dot ",$arrEmail[1]) . "</noscript>";
        return $strTag;
    }
?>

</head>
<body>
<!-- class="yui-t7" -->
<!-- id="doc3" -->
<div id="doc2">
	<div id="hd" role="banner">
		<h1>Gary Gale - geek with a life</h1>
	</div>
	<div id="bd" role="main">
		<!-- About and Events div -->
		<div class="yui-g">
			<!-- About section -->
			<div class="yui-u first">
				<h2 id="abouthead">About ...</h2>
				<a title="Gary at Coit Tower" href="http://www.flickr.com/photos/plasticbag/4475788424/" class="photo"><img src="http://farm3.static.flickr.com/2802/4475788424_ab48545f66_d.jpg" alt="Gary at Coit Tower"></a>
				<p>A self professed <i>"geek with a life"</i>, I've had a life-long love affair with maps since discovering the iconic Harry Beck map of the Underground on the back of the London A-Z street atlas at an early age. After "growing up and getting a proper job" I now live in Teddington in South West London with my family and work in London and Berlin as Director of the <a href="http://www.ovi.com/services/">Ovi</a> Places Registry for <a href="http://www.nokia.com/">Nokia</a>; I'm also the co-founder of <a href="http://wherecamp.eu/">WhereCamp EU</a>, the chair of <a href="http://www.w3gconf.com/">w3gconf</a> and I sit on the <a href="http://www.w3.org/2010/POI/">W3C POI Working Group</a> and the <a href="http://location.defra.gov.uk/">UK Location User Group</a>.</p>

				<p>Prior to Nokia, I was at <a href="http://www.yahoo.com/">Yahoo!</a>, leading their <a href="http://developer.yahoo.com/geo/">Geo Technologies</a> group in the UK, releasing <a href="http://developer.yahoo.com/geo/geoplanet/">GeoPlanet</a> and <a href="http://developer.yahoo.com/geo/placemaker/">Placemaker</a> and providing the geo heavy lifting for <a href="http://www.flickr.com/">Flickr</a> and <a href="http://fireeagle.yahoo.net/">Fire Eagle</a>; I've also been at Digicon, developing geophysical technologies to aid in the search for natural resources and at the <a href="http://www.esa.int/SPECIALS/ESRIN_SITE/index.html">European Space Agency Research Institute</a> in Rome, Italy, participating in the development and launch of <a href="http://earth.esa.int/ers/satconc/">ERS-1</a>, Europe's first remote sensing satellite. Outside of the location and geo field, I've been at companies including the <a href="http://www.bbc.co.uk/worldservice/">BBC World Service</a>, <a href="http://www.reuters.com/">Reuters</a>, <a href="http://www.factiva.com/">Factiva.com</a> and <a href="http://www.mcafee.com/uk/">Network Associates</a>.</p>

				<p>Fascinated by technology, I first started hacking on a <a href="http://www.flickr.com/photos/berangberang/406224504/">Commodore PET</a>, built my own <a href="http://www.flickr.com/photos/caseytech/3532365298/">Sinclair ZX-80</a>, spent too many years behind the console of a <a href="http://www.flickr.com/photos/vax-o-matic/2199715941/">VAX</a>, and even more years coding in Assembler, FORTRAN, C and C++.</p>

				<p>I speak and present at a wide range of conferences, workshops and events including <a href="http://en.oreilly.com/where2010/ ">Where 2.0</a>, <a href="http://www.stateofthemap.org/ ">State of the Map</a>, <a href="http://www.agi.org.uk/agi-geocommunity/">AGI GeoCommunity</a>, <a href="http://gmdlondon.ning.com/ ">#geomob</a>, <a href="http://www.mashupevent.com/">mashup*</a>, the <a href="http://www.bcs.org/ ">British Computer Society</a>, <a href="http://wherecamp.eu/">WhereCamp EU</a>, the <a href="http://www.thewherebusiness.com/locationsummit/">Location Business Summit</a> and <a href="http://futureofwebapps.com/">FOWA</a>.</p>

				<p>Writing as regularly as possible on location, place, maps and other facets of geography, I blog at <a href="http://www.vicchi.org/">www.vicchi.org</a> and I tweet as <a href="http://twitter.com/vicchi">@vicchi</a>.</p>

				<p>Despite living in London for most of my life I still haven't managed to visit every station on the London Underground network and I now face the additional challenge posed by using the Berlin U-Bahn network every week.</p>
    		</div>
			<!-- Events and Travel section -->
    		<div class="yui-u">
				<h2 id="eventshead">Events &amp; Talks</h2>
				<p class="byline">I'm lucky enough to speak on matters geo, location and privacy at various conferences and events; these are the latest ones.</p>
				<?php echo $travels; ?>
				<p class="end"><a href="http://www.vicchi.org/speaking">Full archive of past events</a></p>
    		</div>
		</div>

		<!-- Bio and Contact div -->
		<div class="yui-g">
			<!-- Bio section -->
    		<div class="yui-u first">
				<h2 id="biohead">Bio</h2>
				<p class="byline">A quick bio for cutting and pasting</p>
				<ul id="headshots">
				<li><a title="Being Digital '09 - London, 2009" href="http://www.flickr.com/photos/mashupdigital/3616121767/in/set-72157619596196590/"><img class="pc_img" src="http://farm4.static.flickr.com/3355/3616121767_ca1b285fe7_s_d.jpg" width="75" height="75" alt="Being Digital '09 - London, 2009"></a></li>
				</ul>
				<p>A self professed <i>"geek with a life"</i>, Gary's had a life-long love affair with maps since discovering the iconic Harry Beck map of the London Underground on the back of the London A-Z street atlas at an early age. After "growing up and getting a proper job" he now lives in Teddington in South West London with his family and works in London and Berlin as Director of the <a href="http://www.ovi.com/services/">Ovi</a> Places Registry for <a href="http://www.nokia.com/">Nokia</a>; he's also the co-founder of <a href="http://wherecamp.eu/">WhereCamp EU</a>, the chair of <a href="http://www.w3gconf.com/">w3gconf</a> and sits on the <a href="http://www.w3.org/2010/POI/">W3C POI Working Group</a> and the <a href="http://location.defra.gov.uk/">UK Location User Group</a>.</p>

				<p>Prior to Nokia, Gary was at <a href="http://www.yahoo.com/">Yahoo!</a>, leading their <a href="http://developer.yahoo.com/geo/">Geo Technologies</a> group in the UK, releasing <a href="http://developer.yahoo.com/geo/geoplanet/">GeoPlanet</a> and <a href="http://developer.yahoo.com/geo/placemaker/">Placemaker</a> and providing the geo heavy lifting for <a href="http://www.flickr.com/">Flickr</a> and <a href="http://fireeagle.yahoo.net/">Fire Eagle</a>; he's also been at Digicon, developing geophysical technologies to aid in the search for natural resources and at the <a href="http://www.esa.int/SPECIALS/ESRIN_SITE/index.html">European Space Agency Research Institute</a> in Rome, Italy, participating in the development and launch of <a href="http://earth.esa.int/ers/satconc/">ERS-1</a>, Europe's first remote sensing satellite. Outside of the location and geo field, he's been at companies including the <a href="http://www.bbc.co.uk/worldservice/">BBC World Service</a>, <a href="http://www.reuters.com/">Reuters</a>, <a href="http://www.factiva.com/">Factiva.com</a> and <a href="http://www.mcafee.com/uk/">Network Associates</a>.

				<p>Writing as regularly as possible on location, place, maps and other facets of geography, he blogs at <a href="http://www.vicchi.org/">www.vicchi.org</a> and tweets as <a href="http://twitter.com/vicchi">@vicchi</a>.</p>
    		</div>
			<!-- Contact section -->
    		<div class="yui-u">
				<h2 id="contacthead">Contact</h2>
				<p class="byline">How to get in touch</p>
				<p>The quickest and easiest ways to get in touch are on Twitter, where I'm <a href="http://twitter.com/vicchi">@vicchi</a> or via email to <?php $x=createMailto('gary@vicchi.org'); echo $x; ?>; both of these come through to my iPhone and are checked regularly.</p>
				<p>My bookmarks are on <a href="http://delicious.com/vicchi">delicious</a>, my photos are on <a href="http://www.flickr.com/photos/vicchi/">Flickr</a>, my professional profile is on <a href="http://www.linkedin.com/in/garygale">LinkedIn</a>, what I'm listening to is on <a href="http://www.last.fm/user/vicchi">Last.fm</a> and if you know me, then add me as a friend on <a href="http://www.facebook.com/vicchi">Facebook</a>.</p>

    		</div>
		</div>

		<!-- Decks section -->
		<div class="yui-g">
			<h2 id="deckshead">Decks</h2>
			<p>If there's a deck from a talk I've done, it's on <a href="http://www.slideshare.net/vicchi">SlideShare</a>; these are the latest ones.</p>
			<ul id="presentationlist">
			<?php echo $decks; ?>
			</ul>
			<p class="end"><a href="http://slideshare.net/vicchi">See all of my decks on SlideShare</a></p>
		</div>

		<!-- Blog and Mentions div -->
		<div class="yui-g">
			<!-- Blog section -->
    		<div class="yui-u first">
				<h2 id="bloghead">Blog</h2>
				<p class="byline">Occasional journal entries on work and other stuff.</p>
				<ul id="blog">
				<?php echo $blog; ?>
				<p class="end"><a href="http://www.vicchi.org">Full archive of blog posts is at http://www.vicchi.org</a></p>
				</ul>
    		</div>

			<!-- Mentions section -->
    		<div class="yui-u">
				<h2 id="mentionshead">Mentions</h2>
				<p class="byline">Occasional mentions.</p>
				<ul id="mentions">
					<?php echo $mentions; ?>
				</ul>
				<p class="end"><a href="http://delicious.com/vicchi/garygale+clippings">Full archive of mentions is at http://delicious.com/vicchi/garygale+clippings</a>
			</div>
		</div>

		<!-- Articles section -->
		<div class="yui-g">
			<h2 id="articleshead">Articles</h2>
			<ul id="articles">
				<?php echo $articles; ?>
			</ul>
		</div>

		<!-- Videos section -->
		<div class="yui-g">
			<h2 id="videoshead">Videos</h2>
			<p class="byline">I'm occasionally on video as well.</p>
			<ul id="videos">
			<?php echo $videos; ?>
			</ul>
			<p class="end"><a href="http://delicious.com/vicchi/garygale+videos">Full archive of videos is at http://delicious.com/vicchi/garygale+videos</a>
		</div>

		<!-- Photos section -->
		<div class="yui-g">
			<h2 id="photoshead">Photos</h2>
			<p class="byline">These photos are all hosted on Flickr and may have differing terms of use; click on the photo, go to Flick and check out the small print.</p>
			<ul id="headshots">
				<li><a title="WhereCamp - Palo Alto, 2009 with fellow Yahoos Tyler Bell and Aaron Cope" href="http://www.flickr.com/photos/vicchi/3554626421/in/set-72157618559477609/">
					<img class="pc_img" width="75" height="75" alt="WhereCamp - Palo Alto, 2009 with fellow Yahoos Tyler Bell and Aaron Cope" src="http://farm4.static.flickr.com/3388/3554626421_304e7eeaeb_s_d.jpg"/>
					</a></li>
				<li><a title="#geomob - Google London, 2008" href="http://www.flickr.com/photos/vicchi/3064889725/">
					<img class="pc_img" src="http://farm4.static.flickr.com/3198/3064889725_a6dbb8b36d_s.jpg" width="75" height="75" alt="#geomob - Google, London, 2008" />
					</a></li>
				<li><a title="Spatial Junkies - Yahoo! Sunnyvale, USA, 2009" href="http://www.flickr.com/photos/vicchi/2642873174/in/set-72157606013605267/"><img class="pc_img" src="http://farm4.static.flickr.com/3084/2642873174_c686461f80_s_d.jpg" width="75" height="75" alt="Spatial Junkies - Yahoo! Sunnyvale, USA, 2009">

				<li><a title="#geomob - Google London, 2008" href="http://www.flickr.com/photos/vicchi/3064890131/"> <img class="pc_img" src="http://farm4.static.flickr.com/3150/3064890131_5d7c35ac1f_s.jpg" width="75" height="75" alt="#geomob - Google London 2008"></a></li>

				<li><a title="mashup* - Being Location Aware - Ogilvy Media Lab, London, 2009" href="http://www.flickr.com/photos/mashupdigital/3369834253/"><img class="pc_img" src="http://farm4.static.flickr.com/3449/3369834253_eb33c475ae_s_d.jpg" width="75" height="75" alt="mashup* - Being Location Aware - Ogilvy Media Lab, London, 2009" /></a></li>

				<li><a title="State of the Map '09 - Amsterdam, Netherlands, 2009" href="http://www.flickr.com/photos/79774423@N00/3712835580/"><img class="pc_img" src="http://farm4.static.flickr.com/3423/3712835580_2566170fe1_s_d.jpg" width="75" height="75" alt="State of the Map '09 - Amsterdam, Netherlands, 2009" /></a></li>

				<li><a title="Where 2.0 - San Jose, USA, 2009" href="http://www.flickr.com/photos/vicchi/3549755394/in/set-72157618558875875/"><img class="pc_img" src="http://farm4.static.flickr.com/3328/3549755394_bc011d103e_s_d.jpg" width="75" height="75" alt="Where 2.0 - San Jose, USA, 2009"></img></li>

				<li><a title="Being Digital '09 - London, 2009" href="http://www.flickr.com/photos/mashupdigital/3616121767/in/set-72157619596196590/"><img class="pc_img" src="http://farm4.static.flickr.com/3355/3616121767_ca1b285fe7_s_d.jpg" width="75" height="75" alt="Being Digital '09 - London, 2009"></a></li>

				<li><a title="State of the Map '08 - Limerick, Eire, 2008" href="http://www.flickr.com/photos/copetersen/2683356883/"><img class="pc_img" src="http://farm4.static.flickr.com/3075/2683356883_697bd80fa7_s_d.jpg" width="75" height="75" alt="State of the Map '08 - Limerick, Eire, 2008"></a></li>

				<li><a title="#geomob - Yahoo! London, 2009" href="http://www.flickr.com/photos/sigizmund/3236818787/"><img class="pc_img" src="http://farm4.static.flickr.com/3310/3236818787_22da7fe9b5_s_d.jpg" width="75" heigh="75" alt="#geomob - Yahoo! London, 2009"></a></li>

				<li><a title="Spatial Junkies - Yahoo! Sunnyvale, USA, 2008" href="http://www.flickr.com/photos/vicchi/2642872948/in/set-72157606013605267/"><img class="pc_img" src="http://farm4.static.flickr.com/3101/2642872948_fd2c9eb175_s_d.jpg" width="75" height="75" alt="Spatial Junkies - Yahoo! Sunnyvale, USA, 2008"></a></li>

				<li><a title="Geo celebration at Browns with Mark Law, ex Yahoo! now VP at MapQuest" href="http://www.flickr.com/photos/13398027@N04/2316202510/in/set-72157604064029703/"><img src="http://farm4.static.flickr.com/3238/2316202510_c2a20dca97_s_d.jpg" width="75" height="75" alt="Geo celebration at Browns with Mark Law, ex Yahoo! now VP at MapQuest"></a></li>
			</ul>
		</div>

		<!-- Navigation section -->
		<ul id="nav">
			<li><a href="#abouthead">About</a></li>
			<li><a href="#eventshead">Events</a></li>
			<li><a href="#biohead">Bio</a></li>
			<li><a href="#contacthead">Contact</a></li>
			<li><a href="#deckshead">Decks</a></li>
			<li><a href="#bloghead">Blog</a></li>
			<li><a href="#mentionshead">Mentions</a></li>
			<li><a href="#articleshead">Articles</a></li>
			<li><a href="#videoshead">Videos</a></li>
			<li><a href="#photoshead">Photos</a></li>
			<li><a href="#contacthead">Contact</a></li>
		</ul>
	</div>

	<!-- Footer section -->
	<div id="ft" role="contentinfo">
		<address class="vcard">
		<span class="fn">Gary Gale</span>, <span class="title">Director, Ovi Places for Nokia</span>, based in <span class="adr"><span class="locality">London</span>, <span class="country-name">United Kingdom</span></span> and <span class="locality">Berlin</span>, <span class="country-name">Germany</span></span>.
		</address>
		<p>This site is dynamically updated from <a href="http://delicious.com">delicious</a>, <a href="http://feedburner.google.com">FeedBurner</a> and <a href="http://www.slideshare.net">SlideShare</a> using <a href="http://developer.yahoo.com/yql/">YQL</a> and uses <a href="http://developer.yahoo.com/yui/">YUI</a> for layout.</p>
		<p><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/uk/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/uk/80x15.png" /></a>&nbsp;<span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Text" property="dc:title" rel="dc:type">garygale.com</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="http://www.garygale.com/" property="cc:attributionName" rel="cc:attributionURL">Gary Gale</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/uk/">Creative Commons Attribution-Share Alike 2.0 UK: England &amp; Wales License</a>.</p>

	</div>
</div>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-10486552-2");
pageTracker._trackPageview();
} catch(err) {}</script>
</body>
</html>
