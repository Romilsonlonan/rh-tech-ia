import { useState } from 'react';
import { Button } from '@/components/ui';
import styles from './Navbar.module.css';

const navItems = [
  { label: 'Início', href: '#home' },
  { label: 'Serviços', href: '#services' },
  { label: 'Sobre', href: '#about' },
  { label: 'Contato', href: '#contact' },
];

export const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className={styles.navbar}>
      <div className={styles.container}>
        <a href="#home" className={styles.logo}>
          <span className={styles.logoIcon}>⚡</span>
          <span className={styles.logoText}>RH Tech IA</span>
        </a>

        <div className={styles.desktopMenu}>
          {navItems.map((item) => (
            <a key={item.href} href={item.href} className={styles.navLink}>
              {item.label}
            </a>
          ))}
          <Button variant="primary">Login</Button>
        </div>

        <button 
          className={styles.menuButton}
          onClick={() => setIsOpen(!isOpen)}
          aria-label="Menu"
        >
          <span className={isOpen ? styles.lineRotate : ''}></span>
          <span className={isOpen ? styles.lineHide : ''}></span>
          <span className={isOpen ? styles.lineRotateReverse : ''}></span>
        </button>
      </div>

      {isOpen && (
        <div className={styles.mobileMenu}>
          {navItems.map((item) => (
            <a 
              key={item.href} 
              href={item.href} 
              className={styles.mobileLink}
              onClick={() => setIsOpen(false)}
            >
              {item.label}
            </a>
          ))}
          <Button variant="primary" className={styles.mobileButton}>Login</Button>
        </div>
      )}
    </nav>
  );
};
