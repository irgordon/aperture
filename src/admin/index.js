import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route, NavLink } from 'react-router-dom';
import LeadList from './components/LeadList';
import SettingsPage from './components/SettingsPage';
import './style.scss';

const AdminApp = () => (
    <HashRouter>
        <div className="ap-layout">
            <nav className="ap-nav">
                <h1>AperturePro</h1>
                <NavLink to="/">Pipeline</NavLink>
                <NavLink to="/settings">Settings</NavLink>
            </nav>
            <main>
                <Routes>
                    <Route path="/" element={<LeadList />} />
                    <Route path="/settings" element={<SettingsPage />} />
                </Routes>
            </main>
        </div>
    </HashRouter>
);
const root = document.getElementById('aperture-admin');
if(root) createRoot(root).render(<AdminApp />);
