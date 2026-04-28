/**
 * Quiz interactif dans les articles — adapté de redactionavecia.fr.
 *
 * Structure HTML attendue :
 * <div data-quiz data-results='{"low":"...","mid":"...","high":"..."}'>
 *   <h3>Titre du quiz</h3>
 *   <div data-quiz-missing>Réponds à toutes les questions...</div>
 *   <div data-quiz-step>
 *     <p>Question ?</p>
 *     <label><input type="radio" name="q1" data-points="1"> A</label>
 *     <label><input type="radio" name="q1" data-points="2"> B</label>
 *     <label><input type="radio" name="q1" data-points="3"> C</label>
 *   </div>
 *   ... (3-4 quiz-step)
 *   <button data-quiz-submit>Voir mon résultat</button>
 * </div>
 */
(function () {
  'use strict';

  function initQuizzes() {
    document.querySelectorAll('[data-quiz]').forEach((quiz, quizIndex) => {
      const steps = quiz.querySelectorAll('[data-quiz-step]');
      const submitBtn = quiz.querySelector('[data-quiz-submit]');
      if (!steps.length || !submitBtn) return;

      // FIX : renommer les radios pour qu'elles soient indépendantes par question
      // (certains articles auto-générés ont tous les radios avec name="q1")
      steps.forEach((step, stepIndex) => {
        const uniqueName = 'pamq-' + quizIndex + '-' + stepIndex;
        step.querySelectorAll('input[type="radio"]').forEach((radio) => {
          radio.name = uniqueName;
        });
      });

      let resultsData = {};
      try { resultsData = JSON.parse(quiz.dataset.results || '{}'); } catch (e) {}

      const maxScore = Array.from(steps).reduce((sum, step) => {
        const max = Math.max(...Array.from(step.querySelectorAll('input[data-points]'))
          .map(i => parseInt(i.dataset.points || '0', 10)));
        return sum + (isFinite(max) ? max : 0);
      }, 0);

      submitBtn.addEventListener('click', (e) => {
        e.preventDefault();

        let allAnswered = true;
        let totalScore = 0;
        steps.forEach((step) => {
          const checked = step.querySelector('input[type="radio"]:checked');
          if (!checked) {
            allAnswered = false;
            step.style.borderLeft = '4px solid #c97a1e';
          } else {
            step.style.borderLeft = '';
            totalScore += parseInt(checked.dataset.points || '0', 10);
          }
        });

        if (!allAnswered) {
          const missingMsg = quiz.querySelector('[data-quiz-missing]');
          if (missingMsg) missingMsg.style.display = 'block';
          return;
        }

        const ratio = maxScore > 0 ? totalScore / maxScore : 0;
        let key = 'mid';
        if (ratio < 0.4) key = 'low';
        else if (ratio >= 0.75) key = 'high';

        const resultText = resultsData[key] || 'Résultat';
        const resultBadge = { low: '🌱', mid: '🎯', high: '🏆' }[key];

        const resultPane = document.createElement('div');
        resultPane.style.cssText =
          'margin-top:1.5rem;padding:2rem;border-radius:16px;' +
          'background:linear-gradient(135deg,#1f5742,#3a8a5e);color:#f5f3ec;' +
          'text-align:center;animation:quiz-pop 0.6s cubic-bezier(0.22,1,0.36,1);' +
          'font-family:Inter,sans-serif;';
        resultPane.innerHTML =
          '<div style="font-size:2.5rem;margin-bottom:0.5rem">' + resultBadge + '</div>' +
          '<div style="font-size:13px;opacity:0.85;margin-bottom:0.25rem;font-weight:600;text-transform:uppercase;letter-spacing:0.12em">Ton résultat</div>' +
          '<div style="font-size:1.4rem;font-weight:700;margin-bottom:0.5rem;font-family:Fraunces,serif">' + resultText + '</div>' +
          '<div style="font-size:13px;opacity:0.85">Score : ' + totalScore + ' / ' + maxScore + '</div>';

        steps.forEach((s) => s.style.display = 'none');
        submitBtn.style.display = 'none';
        quiz.appendChild(resultPane);

        setTimeout(() => resultPane.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQuizzes);
  } else {
    initQuizzes();
  }
})();
