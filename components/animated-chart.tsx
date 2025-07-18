"use client"

import { motion } from "framer-motion"
import { useEffect, useState } from "react"

export function AnimatedChart() {
  const [dataPoints, setDataPoints] = useState<number[]>([])

  useEffect(() => {
    // Generate sample yield data
    const points = Array.from({ length: 12 }, (_, i) => {
      const base = 8 + Math.sin(i * 0.5) * 2 + Math.random() * 3
      return Math.max(5, Math.min(15, base))
    })
    setDataPoints(points)
  }, [])

  const maxValue = Math.max(...dataPoints)
  const pathData = dataPoints
    .map((point, index) => {
      const x = (index / (dataPoints.length - 1)) * 100
      const y = 100 - (point / maxValue) * 80
      return `${index === 0 ? "M" : "L"} ${x} ${y}`
    })
    .join(" ")

  return (
    <div className="absolute top-4 right-4 w-32 h-20 bg-black/20 rounded-lg border border-blue-500/20 backdrop-blur-sm p-2">
      <div className="text-xs text-blue-400 mb-1">Yield Performance</div>
      <svg className="w-full h-12" viewBox="0 0 100 100">
        <motion.path
          d={pathData}
          fill="none"
          stroke="url(#chartGradient)"
          strokeWidth="2"
          initial={{ pathLength: 0 }}
          animate={{ pathLength: 1 }}
          transition={{ duration: 2, ease: "easeInOut" }}
        />
        <motion.path
          d={`${pathData} L 100 100 L 0 100 Z`}
          fill="url(#chartFill)"
          initial={{ pathLength: 0 }}
          animate={{ pathLength: 1 }}
          transition={{ duration: 2, ease: "easeInOut", delay: 0.5 }}
        />
        <defs>
          <linearGradient id="chartGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stopColor="#3B82F6" />
            <stop offset="100%" stopColor="#06B6D4" />
          </linearGradient>
          <linearGradient id="chartFill" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.3" />
            <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.05" />
          </linearGradient>
        </defs>
      </svg>
    </div>
  )
}
