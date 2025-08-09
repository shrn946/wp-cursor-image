// Thanks to Paul for helping on the forum thread
// https://codepen.io/akapowl/pen/rNwBLMY/21b35858191b221e86c4a3fd79e80350

gsap.set(".conten img.swipeimage", { yPercent: -50, xPercent: -50 });

let firstEnter;

gsap.utils.toArray(".conten").forEach((el) => {
  const image = el.querySelector("img.swipeimage"),
    setX = gsap.quickTo(image, "x", { duration: 0.4, ease: "power3" }),
    setY = gsap.quickTo(image, "y", { duration: 0.4, ease: "power3" }),
    align = (e) => {
      if (firstEnter) {
        setX(e.clientX, e.clientX); //https://gsap.com/docs/v3/GSAP/gsap.quickTo()/#optionally-define-a-start-value
        setY(e.clientY, e.clientY);
        firstEnter = false;
      } else {
        setX(e.clientX);
        setY(e.clientY);
      }
    },
    startFollow = () => document.addEventListener("mousemove", align),
    stopFollow = () => document.removeEventListener("mousemove", align),
    fade = gsap.to(image, {
      autoAlpha: 1,
      ease: "none",
      paused: true,
      duration: 0.1,
      onReverseComplete: stopFollow
    });

  el.addEventListener("mouseenter", (e) => {
    firstEnter = true;
    fade.play();
    startFollow();
    align(e);
  });

  el.addEventListener("mouseleave", () => fade.reverse());
});