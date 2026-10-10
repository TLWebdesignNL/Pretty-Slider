/**
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Autoplay for Pretty Slider with a pause/play button.
 *
 * The Bootstrap carousel is created without "ride" and "pause" options, so this script decides when the
 * slides rotate: not when the visitor prefers reduced motion, not while keyboard focus (and, unless turned
 * off, the pointer) is on the slider, and not after the visitor pressed pause. An optional line at the top shows the time left.
 */
const initSlider = (slider) => {
  const Carousel = window.bootstrap?.Carousel;
  const button = slider.querySelector('[data-prettyslider-toggle]');
  const inner = slider.querySelector('.carousel-inner');
  const progress = slider.querySelector('.prettyslider-progress');

  if (!Carousel) {
    return;
  }

  const carousel = Carousel.getOrCreateInstance(slider);
  let playing = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let hover = false;
  let focus = false;

  // The progress line fills up during one interval, so it starts again whenever the interval does
  const showProgress = (running) => {
    if (!progress) {
      return;
    }

    progress.classList.remove('is-running');

    if (running) {
      // Reading the width makes the browser restart the animation
      progress.getBoundingClientRect();
      progress.classList.add('is-running');
    }
  };

  const apply = () => {
    const running = playing && !hover && !focus;

    if (running) {
      carousel.cycle();
    } else {
      carousel.pause();
    }

    showProgress(running);
  };

  const render = () => {
    // The pause button is optional
    if (!button) {
      return;
    }

    button.setAttribute('aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay);
    button.classList.toggle('is-paused', !playing);

    // Announce slide changes only when the visitor changes slides, not during rotation
    inner?.setAttribute('aria-live', playing ? 'off' : 'polite');
  };

  button?.addEventListener('click', () => {
    playing = !playing;

    // Pressing play is an explicit request, so start even though pointer or focus is on the slider
    hover = false;
    focus = false;

    render();
    apply();
  });

  // Waiting while the pointer is on the slider is a setting; a large slider is easily under the pointer
  if (slider.dataset.prettysliderHover !== '0') {
    slider.addEventListener('mouseenter', () => {
      hover = true;
      apply();
    });

    slider.addEventListener('mouseleave', () => {
      hover = false;
      apply();
    });
  }

  slider.addEventListener('focusin', (event) => {
    // Only keyboard focus stops the rotation, a mouse click on a control does not
    focus = event.target.matches(':focus-visible');
    apply();
  });

  slider.addEventListener('focusout', (event) => {
    focus = focus && slider.contains(event.relatedTarget);
    apply();
  });

  // The line is empty while the slides change
  slider.addEventListener('slide.bs.carousel', () => showProgress(false));

  // Bootstrap stops the rotation after a swipe, so restore the wanted state after each slide
  slider.addEventListener('slid.bs.carousel', apply);

  if (button) {
    button.hidden = false;
  }

  render();
  apply();
};

document.querySelectorAll('[data-prettyslider]').forEach(initSlider);
