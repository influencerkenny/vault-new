"use client"

import { useEffect, useState } from "react"
import { motion, useInView } from "framer-motion"
import { useRef } from "react"

interface AnimatedCounterProps {
  end: number
  duration?: number
  prefix?: string
  suffix?: string
  decimals?: number
  className?: string
}

export function AnimatedCounter({
  end,
  duration = 2,
  prefix = "",
  suffix = "",
  decimals = 0,
  className = "",
}: AnimatedCounterProps) {
  const [count, setCount] = useState(0)
  const ref = useRef(null)
  const isInView = useInView(ref, { once: true })

  useEffect(() => {
    if (!isInView) return

    let startTime: number
    let animationFrame: number

    const animate = (timestamp: number) => {
      if (!startTime) startTime = timestamp
      const progress = Math.min((timestamp - startTime) / (duration * 1000), 1)

      const easeOutQuart = 1 - Math.pow(1 - progress, 4)
      setCount(end * easeOutQuart)

      if (progress < 1) {
        animationFrame = requestAnimationFrame(animate)
      }
    }

    animationFrame = requestAnimationFrame(animate)
    return () => cancelAnimationFrame(animationFrame)
  }, [end, duration, isInView])

  return (
    <motion.span
      ref={ref}
      className={className}
      initial={{ opacity: 0, y: 20 }}
      animate={isInView ? { opacity: 1, y: 0 } : {}}
      transition={{ duration: 0.6 }}
    >
      {prefix}
      {count.toFixed(decimals)}
      {suffix}
    </motion.span>
  )
}
