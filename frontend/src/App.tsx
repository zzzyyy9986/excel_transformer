import { observer } from 'mobx-react-lite';
import { HomePage } from './pages/HomePage';
import 'bootstrap/dist/css/bootstrap.min.css';
import './App.css';

export const App = observer(function App() {
  return (
    <div className="App">
      <HomePage />
    </div>
  );
});

export default App;
