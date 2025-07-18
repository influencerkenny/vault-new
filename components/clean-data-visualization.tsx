"use client"

import { motion } from "framer-motion"
import { RotatingDataCube } from "./rotating-data-cube"
import { FloatingMetricsPanel } from "./floating-metrics-panel"
import { OrbitalRings } from "./orbital-rings"
import { PulseVisualization } from "./pulse-visualization"

export function CleanDataVisualization() {
  return (
    <div className="relative w-96 h-96">
      {/* Background orbital rings */}
      <div className="opacity-30">
        <OrbitalRings />
      </div>

      {/* Central pulse visualization */}
      <div className="opacity-40">
        <PulseVisualization />
      </div>

      {/* Rotating data cube */}
      <div className="opacity-80">
        <RotatingDataCube />
      </div>

      {/* Floating metrics panels */}
      <FloatingMetricsPanel position="top-left" delay={0.2} />
      <FloatingMetricsPanel position="top-right" delay={0.4} />
      <FloatingMetricsPanel position="bottom-left" delay={0.6} />
      <FloatingMetricsPanel position="bottom-right" delay={0.8} />

      {/* Subtle connecting lines */}
      <motion.div
        className="absolute inset-0 opacity-20"
        initial={{ opacity: 0 }}
        animate={{ opacity: 0.2 }}
        transition={{ duration: 2, delay: 1 }}
      >
        <svg className="w-full h-full" viewBox="0 0 384 384">
          <motion.path
            d="M 96 96 Q 192 150 288 96"
            stroke="url(#connectGradient1)"
            strokeWidth="1"
            fill="none"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, delay: 1.2 }}
          />
          <motion.path
            d="M 96 288 Q 192 234 288 288"
            stroke="url(#connectGradient2)"
            strokeWidth="1"
            fill="none"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, delay: 1.4 }}
          />
          <motion.path
            d="M 96 96 Q 150 192 96 288"
            stroke="url(#connectGradient3)"
            strokeWidth="1"
            fill="none"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, delay: 1.6 }}
          />
          <motion.path
            d="M 288 96 Q 234 192 288 288"
            stroke="url(#connectGradient4)"
            strokeWidth="1"
            fill="none"
            initial={{ pathLength: 0 }}
            animate={{ pathLength: 1 }}
            transition={{ duration: 2, delay: 1.8 }}
          />
          <defs>
            <linearGradient id="connectGradient1">
              <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.6" />
              <stop offset="100%" stopColor="#06B6D4" stopOpacity="0.2" />
            </linearGradient>
            <linearGradient id="connectGradient2">
              <stop offset="0%" stopColor="#06B6D4" stopOpacity="0.6" />
              <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.2" />
            </linearGradient>
            <linearGradient id="connectGradient3">
              <stop offset="0%" stopColor="#8B5CF6" stopOpacity="0.6" />
              <stop offset="100%" stopColor="#06B6D4" stopOpacity="0.2" />
            </linearGradient>
            <linearGradient id="connectGradient4">
              <stop offset="0%" stopColor="#06B6D4" stopOpacity="0.6" />
              <stop offset="100%" stopColor="#8B5CF6" stopOpacity="0.2" />
            </linearGradient>
          </defs>
        </svg>
      </motion.div>

      {/* Central glow effect */}
      <motion.div
        className="absolute inset-0 bg-gradient-radial from-blue-500/10 via-transparent to-transparent rounded-full blur-xl"
        animate={{
          scale: [1, 1.1, 1],
          opacity: [0.3, 0.5, 0.3],
        }}
        transition={{
          duration: 4,
          repeat: Number.POSITIVE_INFINITY,
          ease: "easeInOut",
        }}
      />
    </div>
  )
}
