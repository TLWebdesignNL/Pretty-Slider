# Pretty Slider
Pretty Slider module for Joomla 5.4 and Joomla 6.

Shows a slider with an image, a title and a short description per slide. The slides come from the articles of one or more categories, or you create them yourself in the module.

## Requirements
- Joomla 5.4 or Joomla 6
- PHP 8.1 or newer (Joomla 6 itself requires PHP 8.3)
- A template that loads Bootstrap 5 styles, such as Cassiopeia

## Features
- Slides from the published articles in the selected categories, or from all categories
- Or manual slides: add your own slides with an image, alt text, title, description, link and button
- Optional button on article slides, with your own text
- Bootstrap button classes to pick per button, plus your own class; class settings for the main div, title and description
- Uses the intro image or the full article image, with the other one as fallback; articles without an image are skipped
- Description from the article text, the image alt text or the image caption, cut off at a length you choose
- Article ordering and direction, including random
- Autoplay with a pause button and a setting for the seconds per slide; autoplay does not start for visitors who prefer reduced motion
- Optional bullets to go to a slide, and an optional progress line that fills up until the next slide
- Image alt text, labelled slides and a title element you choose, so the slider works with screen readers and fits the heading structure of the page
- English and Dutch

## Set-up
1. Install the ZIP through **System → Install → Extensions**.
2. Create a **Pretty Slider** site module and assign it to a position and menu items.
3. On the **Settings** tab, choose **Articles** or **Manual slides**.
   - **Articles**: select the categories and set the number of slides and the ordering. Give the articles an intro image or a full article image, with alt text.
   - **Manual slides**: add a row for each slide.
4. Set autoplay and the title element.

## Links
- [Changelog](changelog.xml)
- [Issues and support](https://github.com/TLWebdesignNL/Pretty-Slider/issues)
- [TLWebdesign](https://tlwebdesign.nl/)
