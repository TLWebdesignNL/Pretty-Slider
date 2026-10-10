/**
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Autoplay for Pretty Slider with a pause/play button.
 *
 * The Bootstrap carousel is created without "ride" and "pause" options, so this script decides when the
 * slides rotate: not when the visitor prefers reduced motion, not while the pointer or keyboard focus is
 * on the slider, and not after the visitor pressed pause.
 */
const initSlider = (slider) => {
  const Carousel = window.bootstrap?.Carousel;
  const button = slider.querySelector('[data-prettyslider-toggle]');
  const inner = slider.querySelector('.carousel-inner');

  if (!Carousel || !button) {
    return;
  }

  const carousel = Carousel.getOrCreateInstance(slider);
  let playing = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let hover = false;
  let focus = false;

  const apply = () => {
    if (playing && !hover && !focus) {
      carousel.cycle();
    } else {
      carousel.pause();
    }
  };

  const render = () => {
    button.setAttribute('aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay);
    button.classList.toggle('is-paused', !playing);

    // Announce slide changes only when the visitor changes slides, not during rotation
    inner?.setAttribute('aria-live', playing ? 'off' : 'polite');
  };

  button.addEventListener('click', () => {
    playing = !playing;

    // Pressing play is an explicit request, so start even though pointer or focus is on the slider
    hover = false;
    focus = false;

    render();
    apply();
  });

  slider.addEventListener('mouseenter', () => {
    hover = true;
    apply();
  });

  slider.addEventListener('mouseleave', () => {
    hover = false;
    apply();
  });

  slider.addEventListener('focusin', (event) => {
    // Only keyboard focus stops the rotation, a mouse click on a control does not
    focus = event.target.matches(':focus-visible');
    apply();
  });

  slider.addEventListener('focusout', (event) => {
    focus = focus && slider.contains(event.relatedTarget);
    apply();
  });

  // Bootstrap stops the rotation after a swipe, so restore the wanted state after each slide
  slider.addEventListener('slid.bs.carousel', apply);

  button.hidden = false;
  render();
  apply();
};

document.querySelectorAll('[data-prettyslider]').forEach(initSlider);
