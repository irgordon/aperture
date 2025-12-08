import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route, NavLink } from 'react-router-dom';
import LeadList from './components/LeadList';
import SettingsPage from './components/SettingsPage';
import QuestionnaireBuilder from './components/QuestionnaireBuilder';
import GalleryManager from './components/GalleryManager';
import ContactManager from './components/ContactManager'; // <-- Import new component
import './style.scss';

const AdminApp = () => (
    <HashRouter>
        <div className="ap-layout">
            <nav className="ap-nav">
                <h1>AperturePro</h1>
                <NavLink to="/" end>Pipeline</NavLink>
                <NavLink to="/contacts">Contacts</NavLink> {/* <-- New Menu Item */}
                <NavLink to="/galleries">Galleries</NavLink>
                <NavLink to="/forms">Forms</NavLink>
                <NavLink to="/settings">Settings</NavLink>
            </nav>
            <main>
                <Routes>
                    <Route path="/" element={<LeadList />} />
                    <Route path="/contacts" element={<ContactManager />} /> {/* <-- New Route */}
                    <Route path="/galleries" element={<GalleryManager />} />
                    <Route path="/forms" element={<QuestionnaireBuilder />} />
                    <Route path="/settings" element={<SettingsPage />} />
                </Routes>
            </main>
        </div>
    </HashRouter>
);

const root = document.getElementById('aperture-admin');
if(root) createRoot(root).render(<AdminApp />);
