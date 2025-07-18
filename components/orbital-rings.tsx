"use client"

import { motion } from "framer-motion"

export function OrbitalRings() {
  return (
    <div className="absolute inset-0 flex items-center justify-center">
      {/* Outer ring */}
      <motion.div
        className="absolute w-80 h-80 border border-blue-400/20 rounded-full"
        animate={{ rotate: 360 }}
        transition={{ duration: 30, repeat: Number.POSITIVE_INFINITY, ease: "linear" }}
      >
        {/* Orbital dots */}
        {Array.from({ length: 8 }).map((_, index) => (
          <motion.div
            key={index}
            className="absolute w-2 h-2 bg-blue-400/60 rounded-full"
            style={{
              top: "50%",
              left: "50%",
              transform: `translate(-50%, -50%) rotate(${index * 45}deg) translateY(-160px)`,
            }}
            animate={{
              scale: [1, 1.5, 1],
              opacity: [0.6, 1, 0.6],
            }}
            transition={{
              duration: 2,
              repeat: Number.POSITIVE_INFINITY,
              delay: index * 0.25,
            }}
          />
        ))}
      </motion.div>

      {/* Middle ring */}
      <motion.div
        className="absolute w-60 h-60 border border-cyan-400/20 rounded-full"
        animate={{ rotate: -360 }}
        transition={{ duration: 20, repeat: Number.POSITIVE_INFINITY, ease: "linear" }}
      >
        {Array.from({ length: 6 }).map((_, index) => (
          <motion.div
            key={index}
            className="absolute w-1.5 h-1.5 bg-cyan-400/60 rounded-full"
            style={{
              top: "50%",
              left: "50%",
              transform: `translate(-50%, -50%) rotate(${index * 60}deg) translateY(-120px)`,
            }}
            animate={{
              scale: [1, 1.3, 1],
              opacity: [0.4, 0.8, 0.4],
            }}
            transition={{
              duration: 1.5,
              repeat: Number.POSITIVE_INFINITY,
              delay: index * 0.2,
            }}
          />
        ))}
      </motion.div>

      {/* Inner ring */}
      <motion.div
        className="absolute w-40 h-40 border border-purple-400/20 rounded-full"
        animate={{ rotate: 360 }}
        transition={{ duration: 15, repeat: Number.POSITIVE_INFINITY, ease: "linear" }}
      >
        {Array.from({ length: 4 }).map((_, index) => (
          <motion.div
            key={index}
            className="absolute w-1 h-1 bg-purple-400/60 rounded-full"
            style={{
              top: "50%",
              left: "50%",
              transform: `translate(-50%, -50%) rotate(${index * 90}deg) translateY(-80px)`,
            }}
            animate={{
              scale: [1, 1.2, 1],
              opacity: [0.3, 0.7, 0.3],
            }}
            transition={{
              duration: 1,
              repeat: Number.POSITIVE_INFINITY,
              delay: index * 0.15,
            }}
          />
        ))}
      </motion.div>
    </div>
  )
}
