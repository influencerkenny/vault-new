"use client"

import { useEffect, useState } from "react"

interface PerformanceMetrics {
  fps: number
  frameTime: number
  isLowPerformance: boolean
  memoryUsage?: number
}

export function usePerformanceMonitor() {
  const [metrics, setMetrics] = useState<PerformanceMetrics>({
    fps: 60,
    frameTime: 16.67,
    isLowPerformance: false,
  })

  useEffect(() => {
    let frameCount = 0
    let lastTime = performance.now()
    let animationId: number

    const measurePerformance = (currentTime: number) => {
      frameCount++

      if (currentTime - lastTime >= 1000) {
        const fps = Math.round((frameCount * 1000) / (currentTime - lastTime))
        const frameTime = (currentTime - lastTime) / frameCount

        // Get memory usage if available
        const memoryUsage = (performance as any).memory?.usedJSHeapSize || 0

        setMetrics({
          fps,
          frameTime,
          isLowPerformance: fps < 30 || frameTime > 33,
          memoryUsage,
        })

        // Log performance warnings
        if (fps < 20) {
          console.warn("Low FPS detected:", fps)
        }

        frameCount = 0
        lastTime = currentTime
      }

      animationId = requestAnimationFrame(measurePerformance)
    }

    animationId = requestAnimationFrame(measurePerformance)

    return () => {
      if (animationId) {
        cancelAnimationFrame(animationId)
      }
    }
  }, [])

  return metrics
}
