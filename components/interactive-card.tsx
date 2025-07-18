"use client"

import type React from "react"

import { motion, useMotionValue, useSpring, useTransform } from "framer-motion"
import { Card } from "@/components/ui/card"
import type { ReactNode } from "react"

interface InteractiveCardProps {
  children: ReactNode
  className?: string
}

export function InteractiveCard({ children, className = "" }: InteractiveCardProps) {
  const x = useMotionValue(0)
  const y = useMotionValue(0)

  const mouseXSpring = useSpring(x)
  const mouseYSpring = useSpring(y)

  const rotateX = useTransform(mouseYSpring, [-0.5, 0.5], ["7.5deg", "-7.5deg"])
  const rotateY = useTransform(mouseXSpring, [-0.5, 0.5], ["-7.5deg", "7.5deg"])

  const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
    const rect = e.currentTarget.getBoundingClientRect()
    const width = rect.width
    const height = rect.height
    const mouseX = e.clientX - rect.left
    const mouseY = e.clientY - rect.top
    const xPct = mouseX / width - 0.5
    const yPct = mouseY / height - 0.5
    x.set(xPct)
    y.set(yPct)
  }

  const handleMouseLeave = () => {
    x.set(0)
    y.set(0)
  }

  return (
    <motion.div
      onMouseMove={handleMouseMove}
      onMouseLeave={handleMouseLeave}
      style={{
        rotateY: rotateY,
        rotateX: rotateX,
        transformStyle: "preserve-3d",
      }}
      whileHover={{ scale: 1.05 }}
      transition={{ type: "spring", stiffness: 300, damping: 20 }}
    >
      <Card
        className={`bg-gray-900/50 border-gray-800/50 backdrop-blur-sm hover:border-blue-500/30 transition-all duration-500 group ${className}`}
      >
        <motion.div
          style={{
            transform: "translateZ(20px)",
            transformStyle: "preserve-3d",
          }}
        >
          {children}
        </motion.div>
      </Card>
    </motion.div>
  )
}
