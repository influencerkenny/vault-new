"use client"

import { motion } from "framer-motion"

interface LogoSkeletonProps {
  className?: string
}

export function LogoSkeleton({ className = "h-10 w-auto" }: LogoSkeletonProps) {
  return (
    <motion.div
      className={`bg-gradient-to-r from-gray-800 via-gray-700 to-gray-800 rounded-lg ${className}`}
      style={{ width: "140px", height: "40px" }}
      animate={{
        backgroundPosition: ["0% 50%", "100% 50%", "0% 50%"],
      }}
      transition={{
        duration: 2,
        repeat: Number.POSITIVE_INFINITY,
        ease: "easeInOut",
      }}
      initial={{ opacity: 0.6 }}
    >
      <div className="w-full h-full bg-gradient-to-r from-transparent via-gray-600/20 to-transparent animate-pulse" />
    </motion.div>
  )
}
