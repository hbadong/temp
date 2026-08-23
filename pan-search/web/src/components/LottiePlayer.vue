<template>
  <div class="lottie-player" :style="{ width, height }" ref="container"></div>
</template>

<script>
import lottie from 'lottie-web/build/player/lottie_light'

export default {
  name: 'LottiePlayer',
  props: {
    animationData: {
      type: Object,
      required: true,
    },
    width: {
      type: String,
      default: '120px',
    },
    height: {
      type: String,
      default: '120px',
    },
    loop: {
      type: Boolean,
      default: true,
    },
    autoplay: {
      type: Boolean,
      default: true,
    },
    speed: {
      type: Number,
      default: 1,
    },
    renderer: {
      type: String,
      default: 'svg',
    },
  },
  data() {
    return {
      anim: null,
    }
  },
  mounted() {
    this.initAnimation()
  },
  beforeUnmount() {
    this.destroyAnimation()
  },
  methods: {
    initAnimation() {
      if (!this.$refs.container) return
      this.anim = lottie.loadAnimation({
        container: this.$refs.container,
        renderer: this.renderer,
        loop: this.loop,
        autoplay: this.autoplay,
        animationData: this.animationData,
      })
      this.anim.setSpeed(this.speed)
    },
    destroyAnimation() {
      if (this.anim) {
        this.anim.destroy()
        this.anim = null
      }
    },
    play() {
      this.anim?.play()
    },
    pause() {
      this.anim?.pause()
    },
    stop() {
      this.anim?.stop()
    },
    setSpeed(speed) {
      this.anim?.setSpeed(speed)
    },
    goToAndPlay(frame, isFrame) {
      this.anim?.goToAndPlay(frame, isFrame)
    },
    goToAndStop(frame, isFrame) {
      this.anim?.goToAndStop(frame, isFrame)
    },
  },
}
</script>

<style scoped>
.lottie-player {
  display: inline-block;
  overflow: hidden;
}
</style>