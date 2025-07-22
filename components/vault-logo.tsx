"use client"

import { useState, useEffect } from "react"
import { motion, AnimatePresence } from "framer-motion"
import Image from "next/image"
import { LogoSkeleton } from "./logo-skeleton"

interface VaultLogoProps {
  width?: number
  height?: number
  className?: string
  priority?: boolean
}

export function VaultLogo({ width = 140, height = 40, className = "h-10 w-auto", priority = false }: VaultLogoProps) {
  const [isLoaded, setIsLoaded] = useState(false)
  const [hasError, setHasError] = useState(false)
  const [logo, setLogo] = useState<string>("/vault-logo-new.png")
  const [systemName, setSystemName] = useState<string>("Vault")

  useEffect(() => {
    // Fetch logo and system name from API
    fetch("/api/settings")
      .then(res => res.json())
      .then(data => {
        if (data.logo_path) setLogo(data.logo_path)
        if (data.system_name) setSystemName(data.system_name)
      })
      .catch(() => {})
  }, [])

  const handleLoad = () => {
    setIsLoaded(true)
  }

  const handleError = () => {
    setHasError(true)
    setIsLoaded(true) // Stop showing skeleton even on error
  }

  return (
    <div className="relative">
      <AnimatePresence mode="wait">
        {!isLoaded && !hasError && (
          <motion.div
            key="skeleton"
            initial={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.3 }}
            className="absolute inset-0"
          >
            <LogoSkeleton className={className} />
          </motion.div>
        )}
      </AnimatePresence>

      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: isLoaded ? 1 : 0 }}
        transition={{ duration: 0.3, delay: isLoaded ? 0.1 : 0 }}
      >
        {hasError ? (
          // Fallback text logo if image fails to load
          <motion.div
            className={`flex items-center justify-center bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent font-bold text-2xl ${className}`}
            style={{ width: `${width}px`, height: `${height}px` }}
            initial={{ scale: 0.9 }}
            animate={{ scale: 1 }}
            transition={{ duration: 0.3 }}
          >
            {systemName}
          </motion.div>
        ) : (
          <Image
            src={logo}
            alt={systemName}
            width={width}
            height={height}
            className={className}
            onLoad={handleLoad}
            onError={handleError}
            priority={priority}
            style={{
              opacity: isLoaded ? 1 : 0,
              transition: "opacity 0.3s ease-in-out",
            }}
          />
        )}
      </motion.div>
    </div>
  )
}
